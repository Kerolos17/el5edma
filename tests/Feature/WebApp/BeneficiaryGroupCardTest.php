<?php

namespace Tests\Feature\WebApp;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryGroupCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_leader_sees_the_service_group_card(): void
    {
        $leader = User::factory()->create(['role' => 'family_leader']);
        $group  = ServiceGroup::factory()->create([
            'name'      => 'أسرة النور',
            'leader_id' => $leader->id,
        ]);
        $leader->update(['service_group_id' => $group->id]);

        $beneficiary = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        $this->actingAs($leader)
            ->get(route('app.beneficiary-profile', $beneficiary))
            ->assertOk()
            ->assertSee('أسرة النور')
            ->assertSee(__('web_app.table.leader'));
    }

    public function test_group_card_links_to_the_group_profile(): void
    {
        $leader = User::factory()->create(['role' => 'family_leader']);
        $group  = ServiceGroup::factory()->create(['leader_id' => $leader->id]);
        $leader->update(['service_group_id' => $group->id]);

        $beneficiary = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        $this->actingAs($leader)
            ->get(route('app.beneficiary-profile', $beneficiary))
            ->assertOk()
            ->assertSee(route('app.service-group-profile', ['serviceGroup' => $group->id]), false);
    }
}
