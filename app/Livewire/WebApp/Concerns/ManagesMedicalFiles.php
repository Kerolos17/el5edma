<?php

declare(strict_types=1);

namespace App\Livewire\WebApp\Concerns;

use App\Models\MedicalFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait ManagesMedicalFiles
{
    public bool $showMedicalFileForm = false;

    public ?int $medicalFileBeneficiaryId = null;

    public string $medicalFileTitle = '';

    public string $medicalFileType = 'report';

    public mixed $medicalUploadedFile = null;

    public function openMedicalFileForm(?int $beneficiaryId = null): void
    {
        abort_unless(auth()->user()->can('create', MedicalFile::class), 403);

        $this->resetMedicalFileForm();

        if ($beneficiaryId !== null && $this->beneficiaryOptionsQuery()->whereKey($beneficiaryId)->exists()) {
            $this->medicalFileBeneficiaryId = $beneficiaryId;
        }

        $this->showMedicalFileForm = true;
    }

    public function closeMedicalFileForm(): void
    {
        $this->showMedicalFileForm = false;
        $this->resetMedicalFileForm();
    }

    public function saveMedicalFile(): void
    {
        abort_unless(auth()->user()->can('create', MedicalFile::class), 403);

        $data = $this->validate([
            'medicalFileBeneficiaryId' => ['required', 'integer'],
            'medicalFileTitle' => ['required', 'string', 'max:255'],
            'medicalFileType' => ['required', Rule::in(['report', 'image', 'document'])],
            'medicalUploadedFile' => [
                'required',
                'file',
                'max:5120',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx',
            ],
        ]);

        $beneficiary = $this->beneficiaryOptionsQuery()
            ->whereKey($data['medicalFileBeneficiaryId'])
            ->firstOrFail();

        $path = $this->medicalUploadedFile instanceof TemporaryUploadedFile
            ? $this->medicalUploadedFile->store('medical/' . $beneficiary->id, 'private')
            : null;

        if (! $path) {
            throw ValidationException::withMessages([
                'medicalUploadedFile' => __('web_app.validation.medical_upload_failed'),
            ]);
        }

        MedicalFile::create([
            'beneficiary_id' => $beneficiary->id,
            'file_path' => $path,
            'file_type' => $data['medicalFileType'],
            'title' => $data['medicalFileTitle'],
            'uploaded_by' => auth()->id(),
        ]);

        $this->showMedicalFileForm = false;
        $this->resetMedicalFileForm();
        $this->dispatch('toast', message: __('web_app.toasts.medical_file_uploaded'), type: 'success');
    }

    private function resetMedicalFileForm(): void
    {
        $this->reset([
            'medicalFileBeneficiaryId',
            'medicalFileTitle',
            'medicalFileType',
            'medicalUploadedFile',
        ]);

        $this->medicalFileType = 'report';
    }
}
