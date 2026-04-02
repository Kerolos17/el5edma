<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Beneficiary extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name', 'photo', 'birth_date', 'gender', 'code',
        'phone', 'whatsapp', 'facebook_url', 'instagram_url',
        'guardian_name', 'guardian_phone', 'guardian_relation',
        'father_status', 'father_death_date',
        'mother_status', 'mother_death_date',
        'siblings_count', 'siblings_note',
        'financial_status', 'financial_notes',
        'address_text', 'google_maps_url', 'area', 'governorate',
        'service_group_id', 'assigned_servant_id', 'status',
        'disability_type', 'disability_degree',
        'health_status', 'doctor_name', 'hospital_name',
        'last_medical_update', 'medical_notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date'          => 'date',
            'father_death_date'   => 'date',
            'mother_death_date'   => 'date',
            'last_medical_update' => 'date',
        ];
    }

    // ── Relationships ──

    public function serviceGroup(): BelongsTo
    {
        return $this->belongsTo(ServiceGroup::class);
    }

    public function assignedServant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_servant_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class);
    }

    public function activeMedications(): HasMany
    {
        return $this->hasMany(Medication::class)->where('is_active', true);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class)->latest('visit_date');
    }

    public function scheduledVisits(): HasMany
    {
        return $this->hasMany(ScheduledVisit::class);
    }

    public function medicalFiles(): HasMany
    {
        return $this->hasMany(MedicalFile::class);
    }

    public function prayerRequests(): HasMany
    {
        return $this->hasMany(PrayerRequest::class);
    }

    // ── Accessors ──

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        return "/storage/{$this->photo}";
    }

    public function getWhatsappUrlAttribute(): ?string
    {
        if (! $this->whatsapp && ! $this->phone) {
            return null;
        }
        $clean = preg_replace('/[^0-9]/', '', $this->whatsapp ?? $this->phone);

        return "https://wa.me/2{$clean}";
    }

    public function getGuardianWhatsappUrlAttribute(): ?string
    {
        if (! $this->guardian_phone) {
            return null;
        }
        $clean = preg_replace('/[^0-9]/', '', $this->guardian_phone);

        return "https://wa.me/2{$clean}";
    }

    // ── Auto-generate code ──

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Beneficiary $beneficiary) {
            if (empty($beneficiary->code)) {
                $beneficiary->updateQuietly([
                    'code' => 'SN-' . str_pad($beneficiary->id, 4, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }
}
