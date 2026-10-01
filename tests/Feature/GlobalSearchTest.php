<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\JoinRequest;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_servant_finds_beneficiary_inside_own_group_only(): void
    {
        $group      = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $servant    = User::factory()->create(['service_group_id' => $group->id]);

        $mine = Beneficiary::factory()->create(['full_name' => 'منير أحمد', 'service_group_id' => $group->id]);
        Beneficiary::factory()->create(['full_name' => 'منير الآخر', 'service_group_id' => $otherGroup->id]);

        $this->actingAs($servant)
            ->get(route('app.search', ['q' => 'منير']))
            ->assertOk()
            ->assertSee('منير أحمد')
            ->assertDontSee('منير الآخر');
    }

    public function test_visits_are_found_by_beneficiary_name_and_scoped(): void
    {
        $group       = ServiceGroup::factory()->create();
        $servant     = User::factory()->create(['service_group_id' => $group->id]);
        $beneficiary = Beneficiary::factory()->create(['full_name' => 'سمير فهمي', 'service_group_id' => $group->id]);

        $visit = Visit::factory()->create(['beneficiary_id' => $beneficiary->id]);

        $this->actingAs($servant)
            ->get(route('app.search', ['q' => 'سمير']))
            ->assertOk()
            ->assertSee('سمير فهمي');

        $this->assertDatabaseHas('visits', ['id' => $visit->id]);
    }

    public function test_users_section_appears_only_for_admin_roles(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = User::factory()->create(['role' => 'servant', 'service_group_id' => $group->id]);
        $admin   = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['name' => 'خادم بابا']);

        $this->actingAs($servant)
            ->get(route('app.search', ['q' => 'خادم']))
            ->assertOk()
            ->assertDontSee(__('web_app.shell.search_users'));

        $this->actingAs($admin)
            ->get(route('app.search', ['q' => 'خادم']))
            ->assertOk()
            ->assertSee('خادم بابا');
    }

    public function test_join_requests_section_appears_only_for_reviewers(): void
    {
        $admin     = User::factory()->create(['role' => 'super_admin']);
        $applicant = User::factory()->create(['name' => 'متقدم للبحث']);
        JoinRequest::create(['user_id' => $applicant->id, 'status' => 'pending']);

        $this->actingAs($admin)
            ->get(route('app.search', ['q' => 'متقدم']))
            ->assertOk()
            ->assertSee('متقدم للبحث');
    }

    public function test_empty_query_shows_the_hint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('app.search'))
            ->assertOk()
            ->assertSee(__('web_app.shell.search_empty_hint'));
    }
}
