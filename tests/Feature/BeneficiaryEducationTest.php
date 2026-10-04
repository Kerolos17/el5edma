<?php

namespace Tests\Feature;

use App\Livewire\WebApp\BeneficiariesPage;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BeneficiaryEducationTest extends TestCase
{
    use RefreshDatabase;

    private function formActor(): User
    {
        $group = ServiceGroup::factory()->create();

        return User::factory()->create([
            'role'             => 'family_leader',
            'service_group_id' => $group->id,
        ]);
    }

    public function test_school_beneficiary_saves_place_and_grade(): void
    {
        $actor = $this->formActor();
        $group = ServiceGroup::first();

        Livewire::actingAs($actor)
            ->test(BeneficiariesPage::class)
            ->call('openBeneficiaryForm')
            ->set('beneficiaryFullName', 'طالب مدرسي')
            ->set('beneficiaryBirthDate', '2015-05-10')
            ->set('beneficiaryGender', 'male')
            ->set('beneficiaryServiceGroupId', $group->id)
            ->set('beneficiaryEducationStatus', 'school')
            ->set('beneficiaryEducationPlaceName', 'مدرسة النور الابتدائية')
            ->set('beneficiaryEducationGrade', 'الصف الرابع الابتدائي')
            ->call('saveBeneficiary')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('beneficiaries', [
            'full_name'            => 'طالب مدرسي',
            'education_status'     => 'school',
            'education_place_name' => 'مدرسة النور الابتدائية',
            'education_grade'      => 'الصف الرابع الابتدائي',
        ]);
    }

    public function test_center_beneficiary_keeps_place_but_not_grade(): void
    {
        $actor = $this->formActor();
        $group = ServiceGroup::first();

        Livewire::actingAs($actor)
            ->test(BeneficiariesPage::class)
            ->call('openBeneficiaryForm')
            ->set('beneficiaryFullName', 'طالب مركز')
            ->set('beneficiaryBirthDate', '2012-03-01')
            ->set('beneficiaryGender', 'female')
            ->set('beneficiaryServiceGroupId', $group->id)
            ->set('beneficiaryEducationStatus', 'center')
            ->set('beneficiaryEducationPlaceName', 'مركز التربية الخاصة')
            ->call('saveBeneficiary')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('beneficiaries', [
            'full_name'            => 'طالب مركز',
            'education_status'     => 'center',
            'education_place_name' => 'مركز التربية الخاصة',
        ]);

        $this->assertNull(Beneficiary::where('full_name', 'طالب مركز')->first()->education_grade);
    }

    public function test_none_status_clears_place_and_grade(): void
    {
        $actor       = $this->formActor();
        $group       = ServiceGroup::first();
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id'     => $group->id,
            'education_status'     => 'school',
            'education_place_name' => 'مدرسة قديمة',
            'education_grade'      => 'الصف الخامس',
        ]);

        Livewire::actingAs($actor)
            ->test(BeneficiariesPage::class)
            ->call('openBeneficiaryForm', $beneficiary->id)
            ->assertSet('beneficiaryEducationStatus', 'school')
            ->set('beneficiaryEducationStatus', 'none')
            ->call('saveBeneficiary')
            ->assertHasNoErrors();

        $beneficiary->refresh();
        $this->assertSame('none', $beneficiary->education_status);
        $this->assertNull($beneficiary->education_place_name);
        $this->assertNull($beneficiary->education_grade);
    }

    public function test_education_status_rejects_unknown_values(): void
    {
        $actor = $this->formActor();
        $group = ServiceGroup::first();

        Livewire::actingAs($actor)
            ->test(BeneficiariesPage::class)
            ->call('openBeneficiaryForm')
            ->set('beneficiaryFullName', 'قيم غير صالحة')
            ->set('beneficiaryBirthDate', '2014-01-01')
            ->set('beneficiaryGender', 'male')
            ->set('beneficiaryServiceGroupId', $group->id)
            ->set('beneficiaryEducationStatus', 'university')
            ->call('saveBeneficiary')
            ->assertHasErrors('beneficiaryEducationStatus');
    }
}
