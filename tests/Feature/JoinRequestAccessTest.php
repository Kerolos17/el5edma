<?php

namespace Tests\Feature;

use App\Models\JoinRequest;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Services\RegistrationService;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JoinRequestAccessTest extends TestCase
{
    use RefreshDatabase;

    // ── Pending applicants keep a session but see only the waiting page ──

    public function test_pending_applicant_is_confined_to_the_waiting_page(): void
    {
        $applicant = $this->pendingApplicant();

        $this->actingAs($applicant)
            ->get(route('app.dashboard'))
            ->assertRedirect(route('registration.status'));

        $this->actingAs($applicant)
            ->get(route('app.beneficiaries'))
            ->assertRedirect(route('registration.status'));

        // Even the shared auth-only controller routes are confined.
        $this->actingAs($applicant)
            ->get(route('reports.beneficiaries.pdf'))
            ->assertRedirect(route('registration.status'));

        // And the waiting page itself renders.
        $this->actingAs($applicant)
            ->get(route('registration.status'))
            ->assertOk();
    }

    public function test_waiting_page_shows_own_request_only(): void
    {
        $applicant = $this->pendingApplicant(['name' => 'متقدم معلّق']);
        $other     = User::factory()->create(['name' => 'مستخدم آخر']);

        $this->actingAs($applicant)
            ->get(route('registration.status'))
            ->assertOk()
            ->assertSee('متقدم معلّق')
            ->assertDontSee('مستخدم آخر');
    }

    public function test_pending_applicant_session_survives_confinement(): void
    {
        $applicant = $this->pendingApplicant();

        $this->actingAs($applicant)
            ->get(route('app.dashboard'))
            ->assertRedirect(route('registration.status'));

        $this->assertAuthenticatedAs($applicant);
    }

    // ── Rejected / suspended / legacy inactive sessions are destroyed ──

    public function test_rejected_applicant_session_is_destroyed(): void
    {
        $applicant = $this->pendingApplicant(['is_active' => false]);
        $applicant->joinRequest()->update(['status' => JoinRequest::STATUS_REJECTED]);

        $this->actingAs($applicant)
            ->get(route('app.dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'))
            ->assertSessionHas('error', 'تم رفض طلب انضمامك. تواصل مع المسؤول.');

        $this->assertGuest();
    }

    public function test_suspended_member_session_is_destroyed(): void
    {
        $member = $this->pendingApplicant();
        $member->joinRequest()->update(['status' => JoinRequest::STATUS_APPROVED]);
        $member->forceFill(['is_active' => false, 'suspended_at' => now()])->save();

        $this->actingAs($member)
            ->get(route('app.dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'))
            ->assertSessionHas('error', 'تم إيقاف حسابك مؤقتًا. تواصل مع المسؤول.');

        $this->assertGuest();
    }

    public function test_legacy_inactive_user_without_request_is_logged_out(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('app.dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->assertGuest();
    }

    // ── Active members are completely unaffected ──

    public function test_active_member_is_not_confined(): void
    {
        $member = User::factory()->create(['is_active' => true]);

        $this->actingAs($member)
            ->get(route('servant.dashboard'))
            ->assertOk();

        $this->assertAuthenticatedAs($member);
    }

    // ── Authentication gates ──

    public function test_pending_applicant_can_authenticate_and_lands_on_waiting_page(): void
    {
        $applicant = $this->pendingApplicant();

        $this->assertTrue($applicant->canAccessPanel(
            Panel::make(),
        ));
        $this->assertSame('registration.status', $applicant->homeRoute());
    }

    public function test_rejected_applicant_cannot_authenticate(): void
    {
        $applicant = $this->pendingApplicant();
        $applicant->joinRequest()->update(['status' => JoinRequest::STATUS_REJECTED]);

        $this->assertFalse($applicant->canAccessPanel(Panel::make()));
    }

    // ── Backfill of the historical pending queue ──

    public function test_backfill_creates_pending_requests_for_inactive_users(): void
    {
        $group    = ServiceGroup::factory()->create();
        $inactive = User::factory()->create([
            'is_active'        => false,
            'service_group_id' => $group->id,
        ]);
        $active = User::factory()->create(['is_active' => true]);

        $created = JoinRequest::backfillInactiveUsers();

        $this->assertSame(1, $created);
        $this->assertDatabaseHas('join_requests', [
            'user_id'          => $inactive->id,
            'service_group_id' => $group->id,
            'status'           => JoinRequest::STATUS_PENDING,
            'desired_role'     => 'servant',
        ]);
        $this->assertDatabaseMissing('join_requests', [
            'user_id' => $active->id,
        ]);
    }

    public function test_backfill_is_idempotent(): void
    {
        User::factory()->create(['is_active' => false]);

        JoinRequest::backfillInactiveUsers();
        JoinRequest::backfillInactiveUsers();

        $this->assertSame(1, JoinRequest::count());
    }

    // ── Self-registration creates the request together with the account ──

    public function test_self_registration_creates_a_pending_join_request(): void
    {
        $group = ServiceGroup::factory()->create();
        $data  = [
            'name'     => 'خادم جديد',
            'email'    => 'new-servant@example.com',
            'phone'    => '01000000000',
            'password' => 'secret-password',
        ];

        $user = app(RegistrationService::class)->register($data, $group, '127.0.0.1');

        $this->assertDatabaseHas('join_requests', [
            'user_id'          => $user->id,
            'service_group_id' => $group->id,
            'status'           => JoinRequest::STATUS_PENDING,
            'desired_role'     => 'servant',
        ]);
        $this->assertFalse($user->is_active);
    }

    // ── Helpers ──

    private function pendingApplicant(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge(['is_active' => false], $overrides));

        JoinRequest::create([
            'user_id'          => $user->id,
            'service_group_id' => ServiceGroup::factory()->create()->id,
            'desired_role'     => 'servant',
            'status'           => JoinRequest::STATUS_PENDING,
        ]);

        return $user->refresh();
    }
}
