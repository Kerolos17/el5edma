@if ($showBeneficiaryForm)
    @php
        $parentStatusOptions = [
            'alive' => __('beneficiaries.alive'),
            'deceased' => __('beneficiaries.deceased'),
            'unknown' => __('beneficiaries.unknown'),
        ];
        $financialStatusOptions = [
            'good' => __('beneficiaries.good'),
            'moderate' => __('beneficiaries.moderate'),
            'poor' => __('beneficiaries.poor'),
            'very_poor' => __('beneficiaries.very_poor'),
        ];
        $disabilityDegreeOptions = [
            'mild' => __('beneficiaries.mild'),
            'moderate' => __('beneficiaries.moderate'),
            'severe' => __('beneficiaries.severe'),
        ];
    @endphp

    <div class="app-modal-backdrop" wire:click="closeBeneficiaryForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-labelledby="bene-modal-title">
        <div class="app-modal-panel app-modal-panel-wide" tabindex="-1">
            <div class="app-modal-header">
                <div>
                    <p class="app-section-label">{{ __('web_app.forms.beneficiary.section') }}</p>
                    <h3 id="bene-modal-title">{{ $editingBeneficiaryId ? __('web_app.forms.beneficiary.edit_title') : __('web_app.forms.beneficiary.create_title') }}</h3>
                </div>
                <button type="button" wire:click="closeBeneficiaryForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>

            <div class="app-form-stack">
                <section class="app-form-section">
                    <div class="app-form-section-heading">
                        <i class="ph ph-user" aria-hidden="true"></i>
                        <h4>{{ __('beneficiaries.tab_basic') }}</h4>
                    </div>
                    <div class="app-form-grid">
                        <label class="app-form-field app-form-field-full">
                            <span>{{ __('beneficiaries.photo') }}</span>
                            <input type="file" wire:model="beneficiaryPhoto" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/bmp">
                            <em>{{ __('beneficiaries.photo_helper') }}</em>
                            @error('beneficiaryPhoto') <small>{{ $message }}</small> @enderror
                        </label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.full_name') }}</span><input type="text" wire:model="beneficiaryFullName" placeholder="{{ __('web_app.forms.placeholders.beneficiary_name') }}">@error('beneficiaryFullName') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.birth_date') }}</span><input type="date" wire:model="beneficiaryBirthDate">@error('beneficiaryBirthDate') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field">
                            <span>{{ __('beneficiaries.gender') }}</span>
                            <select wire:model="beneficiaryGender">
                                <option value="">{{ __('web_app.forms.select.gender') }}</option>
                                <option value="male">{{ __('beneficiaries.male') }}</option>
                                <option value="female">{{ __('beneficiaries.female') }}</option>
                            </select>
                            @error('beneficiaryGender') <small>{{ $message }}</small> @enderror
                        </label>
                        <label class="app-form-field">
                            <span>{{ __('beneficiaries.status') }}</span>
                            <select wire:model="beneficiaryRecordStatus">
                                @foreach ($beneficiaryRecordStatusOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('beneficiaryRecordStatus') <small>{{ $message }}</small> @enderror
                        </label>
                    </div>
                </section>

                <section class="app-form-section">
                    <div class="app-form-section-heading">
                        <i class="ph ph-phone" aria-hidden="true"></i>
                        <h4>{{ __('beneficiaries.contact_section') }}</h4>
                    </div>
                    <div class="app-form-grid">
                        <label class="app-form-field"><span>{{ __('beneficiaries.phone') }}</span><input type="text" wire:model="beneficiaryPhone" placeholder="{{ __('web_app.forms.placeholders.phone') }}">@error('beneficiaryPhone') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.whatsapp') }}</span><input type="text" wire:model="beneficiaryWhatsapp" placeholder="{{ __('web_app.forms.placeholders.whatsapp') }}">@error('beneficiaryWhatsapp') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.facebook_url') }}</span><input type="url" wire:model="beneficiaryFacebookUrl" placeholder="{{ __('web_app.forms.placeholders.optional') }}">@error('beneficiaryFacebookUrl') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.instagram_url') }}</span><input type="url" wire:model="beneficiaryInstagramUrl" placeholder="{{ __('web_app.forms.placeholders.optional') }}">@error('beneficiaryInstagramUrl') <small>{{ $message }}</small> @enderror</label>
                    </div>
                </section>

                <section class="app-form-section">
                    <div class="app-form-section-heading">
                        <i class="ph ph-users-three" aria-hidden="true"></i>
                        <h4>{{ __('beneficiaries.guardian_section') }}</h4>
                    </div>
                    <div class="app-form-grid">
                        <label class="app-form-field"><span>{{ __('beneficiaries.guardian_name') }}</span><input type="text" wire:model="beneficiaryGuardianName" placeholder="{{ __('web_app.forms.placeholders.optional') }}">@error('beneficiaryGuardianName') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.guardian_phone') }}</span><input type="text" wire:model="beneficiaryGuardianPhone" placeholder="{{ __('web_app.forms.placeholders.optional') }}">@error('beneficiaryGuardianPhone') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.guardian_relation') }}</span><input type="text" wire:model="beneficiaryGuardianRelation" placeholder="{{ __('web_app.forms.placeholders.optional') }}">@error('beneficiaryGuardianRelation') <small>{{ $message }}</small> @enderror</label>
                    </div>
                </section>

                <section class="app-form-section">
                    <div class="app-form-section-heading">
                        <i class="ph ph-heart" aria-hidden="true"></i>
                        <div>
                            <h4>{{ __('beneficiaries.family_section') }}</h4>
                            <p>{{ __('beneficiaries.family_note') }}</p>
                        </div>
                    </div>
                    <div class="app-form-grid">
                        <label class="app-form-field"><span>{{ __('beneficiaries.father_status') }}</span><select wire:model="beneficiaryFatherStatus"><option value="">{{ __('web_app.forms.select.status') }}</option>@foreach ($parentStatusOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@error('beneficiaryFatherStatus') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.father_death_date') }}</span><input type="date" wire:model="beneficiaryFatherDeathDate">@error('beneficiaryFatherDeathDate') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.mother_status') }}</span><select wire:model="beneficiaryMotherStatus"><option value="">{{ __('web_app.forms.select.status') }}</option>@foreach ($parentStatusOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@error('beneficiaryMotherStatus') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.mother_death_date') }}</span><input type="date" wire:model="beneficiaryMotherDeathDate">@error('beneficiaryMotherDeathDate') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.siblings_count') }}</span><input type="number" min="0" max="30" wire:model="beneficiarySiblingsCount">@error('beneficiarySiblingsCount') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.siblings_note') }}</span><input type="text" wire:model="beneficiarySiblingsNote" placeholder="{{ __('web_app.forms.placeholders.optional') }}">@error('beneficiarySiblingsNote') <small>{{ $message }}</small> @enderror</label>
                    </div>
                </section>

                <section class="app-form-section">
                    <div class="app-form-section-heading">
                        <i class="ph ph-wallet" aria-hidden="true"></i>
                        <h4>{{ __('beneficiaries.financial_section') }}</h4>
                    </div>
                    <div class="app-form-grid">
                        <label class="app-form-field"><span>{{ __('beneficiaries.financial_status') }}</span><select wire:model="beneficiaryFinancialStatus"><option value="">{{ __('web_app.forms.select.status') }}</option>@foreach ($financialStatusOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@error('beneficiaryFinancialStatus') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field app-form-field-full"><span>{{ __('beneficiaries.financial_notes') }}</span><textarea wire:model="beneficiaryFinancialNotes" rows="2" placeholder="{{ __('web_app.forms.placeholders.optional') }}"></textarea>@error('beneficiaryFinancialNotes') <small>{{ $message }}</small> @enderror</label>
                    </div>
                </section>

                <section class="app-form-section">
                    <div class="app-form-section-heading">
                        <i class="ph ph-map-pin" aria-hidden="true"></i>
                        <h4>{{ __('beneficiaries.tab_address') }}</h4>
                    </div>
                    <div class="app-form-grid">
                        <label class="app-form-field app-form-field-full"><span>{{ __('beneficiaries.address_text') }}</span><textarea wire:model="beneficiaryAddressText" rows="3" placeholder="{{ __('web_app.forms.placeholders.address') }}"></textarea>@error('beneficiaryAddressText') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.area') }}</span><input type="text" wire:model="beneficiaryArea" placeholder="{{ __('web_app.forms.placeholders.optional') }}">@error('beneficiaryArea') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.governorate') }}</span><input type="text" wire:model="beneficiaryGovernorate" placeholder="{{ __('web_app.forms.placeholders.optional') }}">@error('beneficiaryGovernorate') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field app-form-field-full"><span>{{ __('beneficiaries.google_maps_url') }}</span><input type="url" wire:model="beneficiaryGoogleMapsUrl" placeholder="{{ __('web_app.forms.placeholders.optional') }}">@error('beneficiaryGoogleMapsUrl') <small>{{ $message }}</small> @enderror</label>
                    </div>
                </section>

                <section class="app-form-section">
                    <div class="app-form-section-heading">
                        <i class="ph ph-first-aid-kit" aria-hidden="true"></i>
                        <h4>{{ __('beneficiaries.tab_medical') }}</h4>
                    </div>
                    <div class="app-form-grid">
                        <label class="app-form-field"><span>{{ __('beneficiaries.disability_type') }}</span><input type="text" wire:model="beneficiaryDisabilityType" placeholder="{{ __('web_app.forms.placeholders.optional') }}">@error('beneficiaryDisabilityType') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.disability_degree') }}</span><select wire:model="beneficiaryDisabilityDegree"><option value="">{{ __('web_app.forms.select.status') }}</option>@foreach ($disabilityDegreeOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@error('beneficiaryDisabilityDegree') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.doctor_name') }}</span><input type="text" wire:model="beneficiaryDoctorName" placeholder="{{ __('web_app.forms.placeholders.optional') }}">@error('beneficiaryDoctorName') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.hospital_name') }}</span><input type="text" wire:model="beneficiaryHospitalName" placeholder="{{ __('web_app.forms.placeholders.optional') }}">@error('beneficiaryHospitalName') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field"><span>{{ __('beneficiaries.last_medical_update') }}</span><input type="date" wire:model="beneficiaryLastMedicalUpdate">@error('beneficiaryLastMedicalUpdate') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field app-form-field-full"><span>{{ __('beneficiaries.health_status') }}</span><textarea wire:model="beneficiaryHealthStatus" rows="3" placeholder="{{ __('web_app.forms.placeholders.optional') }}"></textarea>@error('beneficiaryHealthStatus') <small>{{ $message }}</small> @enderror</label>
                        <label class="app-form-field app-form-field-full"><span>{{ __('beneficiaries.medical_notes') }}</span><textarea wire:model="beneficiaryMedicalNotes" rows="3" placeholder="{{ __('web_app.forms.placeholders.optional') }}"></textarea>@error('beneficiaryMedicalNotes') <small>{{ $message }}</small> @enderror</label>
                    </div>
                </section>

                <section class="app-form-section">
                    <div class="app-form-section-heading">
                        <i class="ph ph-tree-structure" aria-hidden="true"></i>
                        <h4>{{ __('beneficiaries.assignment_section') }}</h4>
                    </div>
                    <div class="app-form-grid">
                        <label class="app-form-field">
                            <span>{{ __('beneficiaries.service_group') }}</span>
                            <select wire:model.live="beneficiaryServiceGroupId" @disabled(auth()->user()->isFamilyLeader())>
                                <option value="">{{ __('web_app.forms.select.group') }}</option>
                                @foreach ($beneficiaryServiceGroupOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('beneficiaryServiceGroupId') <small>{{ $message }}</small> @enderror
                        </label>
                        <label class="app-form-field">
                            <span>{{ __('beneficiaries.assigned_servant') }}</span>
                            <select wire:model="beneficiaryAssignedServantId">
                                <option value="">{{ __('web_app.fallback.unassigned') }}</option>
                                @foreach ($beneficiaryServantOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('beneficiaryAssignedServantId') <small>{{ $message }}</small> @enderror
                        </label>
                    </div>
                </section>
            </div>

            <div class="app-modal-actions">
                <button type="button" wire:click="closeBeneficiaryForm" class="app-secondary-button">{{ __('web_app.actions.cancel') }}</button>
                <button type="button" wire:click="saveBeneficiary" wire:loading.attr="disabled" class="app-primary-button">{{ __('web_app.forms.beneficiary.save') }}</button>
            </div>
        </div>
    </section>
@endif
