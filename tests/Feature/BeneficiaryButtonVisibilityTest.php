<?php
namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BeneficiaryButtonVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function servant_cannot_see_create_button_in_list_page()
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $servant      = User::factory()->create([
            'role'             => UserRole::Servant,
            'service_group_id' => $serviceGroup->id,
        ]);

        $this->actingAs($servant);

        $component = Livewire::test(\App\Filament\Resources\Beneficiaries\Pages\ListBeneficiaries::class);

        // Check that create action is not visible
        $component->assertActionHidden('create');
    }

    /** @test */
    public function family_leader_can_see_create_button_in_list_page()
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $familyLeader = User::factory()->create([
            'role'             => UserRole::FamilyLeader,
            'service_group_id' => $serviceGroup->id,
        ]);

        $this->actingAs($familyLeader);

        $component = Livewire::test(\App\Filament\Resources\Beneficiaries\Pages\ListBeneficiaries::class);

        // Check that create action is visible
        $component->assertActionVisible('create');
    }

    /** @test */
    public function servant_cannot_see_edit_button_in_view_page()
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $servant      = User::factory()->create([
            'role'             => UserRole::Servant,
            'service_group_id' => $serviceGroup->id,
        ]);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id'    => $serviceGroup->id,
            'assigned_servant_id' => $servant->id,
        ]);

        $this->actingAs($servant);

        $component = Livewire::test(
            \App\Filament\Resources\Beneficiaries\Pages\ViewBeneficiary::class,
            ['record' => $beneficiary->id]
        );

        // Check that edit action is not visible
        $component->assertActionHidden('edit');
    }

    /** @test */
    public function family_leader_can_see_edit_button_in_view_page()
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $familyLeader = User::factory()->create([
            'role'             => UserRole::FamilyLeader,
            'service_group_id' => $serviceGroup->id,
        ]);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup->id,
        ]);

        $this->actingAs($familyLeader);

        $component = Livewire::test(
            \App\Filament\Resources\Beneficiaries\Pages\ViewBeneficiary::class,
            ['record' => $beneficiary->id]
        );

        // Check that edit action is visible
        $component->assertActionVisible('edit');
    }

    /** @test */
    public function super_admin_can_see_all_buttons()
    {
        $admin       = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $beneficiary = Beneficiary::factory()->create();

        $this->actingAs($admin);

        // Test list page
        $listComponent = Livewire::test(\App\Filament\Resources\Beneficiaries\Pages\ListBeneficiaries::class);
        $listComponent->assertActionVisible('create');

        // Test view page
        $viewComponent = Livewire::test(
            \App\Filament\Resources\Beneficiaries\Pages\ViewBeneficiary::class,
            ['record' => $beneficiary->id]
        );
        $viewComponent->assertActionVisible('edit');
    }
}
