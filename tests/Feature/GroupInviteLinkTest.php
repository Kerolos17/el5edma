<?php

namespace Tests\Feature;

use App\Livewire\WebApp\ServiceGroupProfilePage;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GroupInviteLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_leader_can_generate_and_share_the_invite_link(): void
    {
        $group  = ServiceGroup::factory()->create();
        $leader = User::factory()->create(['role' => 'family_leader', 'service_group_id' => $group->id]);

        Livewire::actingAs($leader)
            ->test(ServiceGroupProfilePage::class, ['serviceGroup' => $group])
            ->assertSee(__('service_groups.invite_title'))
            ->call('ensureRegistrationLink')
            ->assertDispatched('toast');

        $group->refresh();
        $this->assertNotNull($group->registration_token);

        $html = Livewire::actingAs($leader)
            ->test(ServiceGroupProfilePage::class, ['serviceGroup' => $group->fresh()])
            ->assertSee('/register/')
            ->assertSee(__('service_groups.share_whatsapp'))
            ->html();

        $this->assertStringContainsString('wa.me/?text=', $html);
    }

    public function test_regenerate_invalidates_the_previous_token(): void
    {
        $group  = ServiceGroup::factory()->create(['registration_token' => 'old-token-value']);
        $leader = User::factory()->create(['role' => 'family_leader', 'service_group_id' => $group->id]);

        Livewire::actingAs($leader)
            ->test(ServiceGroupProfilePage::class, ['serviceGroup' => $group])
            ->call('regenerateRegistrationLink');

        $group->refresh();
        $this->assertNotSame('old-token-value', $group->registration_token);
    }

    public function test_servant_cannot_see_or_manage_the_invite_link(): void
    {
        $group   = ServiceGroup::factory()->create(['registration_token' => 'some-token']);
        $servant = User::factory()->create(['role' => 'servant', 'service_group_id' => $group->id]);

        Livewire::actingAs($servant)
            ->test(ServiceGroupProfilePage::class, ['serviceGroup' => $group])
            ->assertDontSee(__('service_groups.invite_title'));

        $this->assertFalse($servant->can('manageRegistrationLink', $group));
    }
}
