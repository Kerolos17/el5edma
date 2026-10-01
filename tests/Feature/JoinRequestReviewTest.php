<?php

namespace Tests\Feature;

use App\Livewire\WebApp\JoinRequestsPage;
use App\Models\JoinRequest;
use App\Models\MinistryNotification;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Services\JoinRequestReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class JoinRequestReviewTest extends TestCase
{
    use RefreshDatabase;

    // ── Service: approval ──

    public function test_super_admin_approval_activates_account_and_records_everything(): void
    {
        [$applicant, $reviewer, $group] = $this->setupRequest();
        $newGroup                       = ServiceGroup::factory()->create();

        $service = app(JoinRequestReviewService::class);
        $service->approve($applicant->joinRequest, $reviewer, 'family_leader', $newGroup->id);

        $applicant->refresh();
        $this->assertTrue($applicant->is_active);
        $this->assertSame('family_leader', $applicant->role->value);
        $this->assertSame($newGroup->id, $applicant->service_group_id);
        $this->assertNull($applicant->suspended_at);

        $request = $applicant->joinRequest()->first();
        $this->assertSame(JoinRequest::STATUS_APPROVED, $request->status);
        $this->assertSame($reviewer->id, $request->reviewed_by);
        $this->assertSame('family_leader', $request->final_role);
        $this->assertSame($newGroup->id, $request->final_service_group_id);

        $this->assertDatabaseHas('join_request_reviews', [
            'join_request_id' => $request->id,
            'reviewer_id'     => $reviewer->id,
            'action'          => 'approved',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'    => $reviewer->id,
            'action'     => 'join_request_approved',
            'model_type' => JoinRequest::class,
        ]);
    }

    public function test_service_leader_cannot_approve_outside_managed_groups(): void
    {
        [$applicant, $reviewer] = $this->setupRequest(reviewerRole: 'service_leader', reviewerGroup: true);
        $foreignGroup           = ServiceGroup::factory()->create();

        $this->expectException(ValidationException::class);

        app(JoinRequestReviewService::class)->approve(
            $applicant->joinRequest,
            $reviewer,
            'servant',
            $foreignGroup->id,
        );
    }

    public function test_family_leader_cannot_grant_a_leadership_role(): void
    {
        [$applicant, $reviewer, $group] = $this->setupRequest(reviewerRole: 'family_leader', reviewerGroup: true);

        $this->expectException(ValidationException::class);

        app(JoinRequestReviewService::class)->approve(
            $applicant->joinRequest,
            $reviewer,
            'family_leader',
            $group->id,
        );
    }

    public function test_cannot_approve_an_already_decided_request(): void
    {
        [$applicant, $reviewer, $group] = $this->setupRequest();
        $service                        = app(JoinRequestReviewService::class);
        $service->approve($applicant->joinRequest, $reviewer, 'servant', $group->id);

        // Re-approval (e.g. a stale browser tab) must fail.
        $this->expectException(ValidationException::class);
        $service->approve($applicant->joinRequest, $reviewer, 'servant', $group->id);
    }

    // ── Service: rejection & changes ──

    public function test_rejection_stores_reason_and_audit_trail(): void
    {
        [$applicant, $reviewer] = $this->setupRequest();

        app(JoinRequestReviewService::class)->reject($applicant->joinRequest, $reviewer, 'بيانات غير مكتملة');

        $request = $applicant->joinRequest()->first();
        $this->assertSame(JoinRequest::STATUS_REJECTED, $request->status);
        $this->assertSame('بيانات غير مكتملة', $request->decision_note);

        $this->assertDatabaseHas('join_request_reviews', ['action' => 'rejected', 'note' => 'بيانات غير مكتملة']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'join_request_rejected']);

        // The rejected account stays gated.
        $this->assertFalse($applicant->refresh()->is_active);
    }

    public function test_rejection_requires_a_reason(): void
    {
        [$applicant, $reviewer] = $this->setupRequest();

        $this->expectException(ValidationException::class);
        app(JoinRequestReviewService::class)->reject($applicant->joinRequest, $reviewer, '  ');
    }

    public function test_requesting_changes_moves_request_to_incomplete(): void
    {
        [$applicant, $reviewer] = $this->setupRequest();

        app(JoinRequestReviewService::class)->requestChanges($applicant->joinRequest, $reviewer, 'أكمل رقم الهاتف');

        $request = $applicant->joinRequest()->first();
        $this->assertSame(JoinRequest::STATUS_INCOMPLETE, $request->status);
        $this->assertDatabaseHas('join_request_reviews', ['action' => 'changes_requested']);
    }

    // ── Service: suspension ──

    public function test_approved_account_can_be_suspended_and_reactivated(): void
    {
        [$applicant, $reviewer, $group] = $this->setupRequest();
        $service                        = app(JoinRequestReviewService::class);
        $service->approve($applicant->joinRequest, $reviewer, 'servant', $group->id);

        $service->suspend($applicant->refresh(), $reviewer);

        $this->assertFalse($applicant->refresh()->is_active);
        $this->assertNotNull($applicant->suspended_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account_suspended']);

        $service->reactivate($applicant, $reviewer);

        $this->assertTrue($applicant->refresh()->is_active);
        $this->assertNull($applicant->suspended_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account_reactivated']);
    }

    // ── Page access & scoping ──

    public function test_servants_cannot_open_the_review_page(): void
    {
        $servant = User::factory()->create();

        $this->actingAs($servant)
            ->get(route('app.join-requests'))
            ->assertForbidden();
    }

    public function test_super_admin_sees_pending_requests_on_the_page(): void
    {
        [$applicant, $reviewer] = $this->setupRequest();

        $this->actingAs($reviewer)
            ->get(route('app.join-requests'))
            ->assertOk()
            ->assertSee($applicant->name);
    }

    public function test_family_leader_sees_only_own_group_requests(): void
    {
        $ownGroup   = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();

        $leader = User::factory()->create([
            'role'             => 'family_leader',
            'service_group_id' => $ownGroup->id,
        ]);

        $own   = User::factory()->create(['is_active' => false, 'service_group_id' => $ownGroup->id]);
        $other = User::factory()->create(['is_active' => false, 'service_group_id' => $otherGroup->id]);

        JoinRequest::create(['user_id' => $own->id, 'service_group_id' => $ownGroup->id, 'status' => 'pending']);
        JoinRequest::create(['user_id' => $other->id, 'service_group_id' => $otherGroup->id, 'status' => 'pending']);

        Livewire::actingAs($leader)
            ->test(JoinRequestsPage::class)
            ->assertOk()
            ->assertSee($own->name)
            ->assertDontSee($other->name);
    }

    public function test_review_action_from_the_page_end_to_end(): void
    {
        [$applicant, $reviewer, $group] = $this->setupRequest();

        Livewire::actingAs($reviewer)
            ->test(JoinRequestsPage::class)
            ->call('openReview', $applicant->joinRequest->id, 'approve')
            ->set('reviewRole', 'servant')
            ->set('reviewGroupId', (string) $group->id)
            ->call('submitReview')
            ->assertHasNoErrors();

        $this->assertTrue($applicant->refresh()->is_active);
        $this->assertSame(JoinRequest::STATUS_APPROVED, $applicant->joinRequest()->first()->status);
        $this->assertDatabaseHas('join_request_reviews', ['action' => 'approved']);
    }

    public function test_approval_notifies_the_applicant_with_a_dedupe_key(): void
    {
        [$applicant, $reviewer, $group] = $this->setupRequest();

        app(JoinRequestReviewService::class)->approve($applicant->joinRequest, $reviewer, 'servant', $group->id);

        $notifications = MinistryNotification::where('user_id', $applicant->id)
            ->where('type', 'join_request_decision')->get();

        $this->assertSame(1, $notifications->count());
        $this->assertSame('join_request:' . $applicant->joinRequest->id . ':decision:approved', $notifications->first()->dedupe_key);
        $this->assertSame(route('registration.status'), $notifications->first()->data['url'] ?? null);
    }

    // ── Helpers ──

    /**
     * @return array{0: User, 1: User, 2: ServiceGroup}
     */
    private function setupRequest(string $reviewerRole = 'super_admin', bool $reviewerGroup = false): array
    {
        $group = ServiceGroup::factory()->create();

        $applicant = User::factory()->create([
            'is_active'        => false,
            'service_group_id' => $group->id,
        ]);

        JoinRequest::create([
            'user_id'          => $applicant->id,
            'service_group_id' => $group->id,
            'desired_role'     => 'servant',
            'status'           => JoinRequest::STATUS_PENDING,
        ]);

        $reviewer = User::factory()->create([
            'role'             => $reviewerRole,
            'service_group_id' => $reviewerGroup ? $group->id : null,
        ]);

        return [$applicant, $reviewer, $group];
    }
}
