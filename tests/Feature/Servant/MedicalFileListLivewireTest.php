<?php

declare(strict_types=1);

namespace Tests\Feature\Servant;

use App\Livewire\Servant\MedicalFileList;
use App\Models\Beneficiary;
use App\Models\MedicalFile;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class MedicalFileListLivewireTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    #[Test]
    public function servant_sees_medical_files_for_their_beneficiaries(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $mine  = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id, 'service_group_id' => $group->id]);
        $other = Beneficiary::factory()->create(['service_group_id' => ServiceGroup::factory()->create()->id]);

        $myFile    = MedicalFile::factory()->create(['beneficiary_id' => $mine->id,  'uploaded_by' => $servant->id]);
        $otherFile = MedicalFile::factory()->create(['beneficiary_id' => $other->id, 'uploaded_by' => $servant->id]);

        Livewire::actingAs($servant)
            ->test(MedicalFileList::class)
            ->assertViewHas('medicalFiles', fn ($files) => $files->contains('id', $myFile->id))
            ->assertViewHas('medicalFiles', fn ($files) => ! $files->contains('id', $otherFile->id));
    }

    #[Test]
    public function servant_can_filter_by_file_type(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id, 'service_group_id' => $group->id]);

        $report = MedicalFile::factory()->create(['beneficiary_id' => $b->id, 'file_type' => 'report',   'uploaded_by' => $servant->id]);
        $image  = MedicalFile::factory()->create(['beneficiary_id' => $b->id, 'file_type' => 'image',    'uploaded_by' => $servant->id]);

        Livewire::actingAs($servant)
            ->test(MedicalFileList::class)
            ->set('filter', 'report')
            ->assertViewHas('medicalFiles', fn ($files) => $files->contains('id', $report->id))
            ->assertViewHas('medicalFiles', fn ($files) => ! $files->contains('id', $image->id));
    }

    #[Test]
    public function medical_file_list_is_read_only(): void
    {
        $reflection = new \ReflectionClass(MedicalFileList::class);
        $this->assertFalse($reflection->hasMethod('create'), 'MedicalFileList should not have a create method');
        $this->assertFalse($reflection->hasMethod('delete'), 'MedicalFileList should not have a delete method');
        $this->assertFalse($reflection->hasMethod('upload'), 'MedicalFileList should not have an upload method');
    }
}
