@if ($showServiceGroupForm)
    <div class="app-modal-backdrop" wire:click="closeServiceGroupForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="{{ __('web_app.forms.service_group.manage') }}">
        <div class="app-modal-panel" tabindex="-1">
            <div class="app-modal-header">
                <div><p class="app-section-label">{{ __('web_app.forms.service_group.section') }}</p><h3>{{ $editingServiceGroupId ? __('web_app.forms.service_group.edit_title') : __('web_app.forms.service_group.create_title') }}</h3></div>
                <button type="button" wire:click="closeServiceGroupForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="app-form-grid">
                <label class="app-form-field"><span>{{ __('service_groups.name') }}</span><input type="text" wire:model="serviceGroupName" placeholder="{{ __('web_app.forms.placeholders.service_group_name') }}">@error('serviceGroupName') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field">
                    <span>{{ __('service_groups.service_leader') }}</span>
                    <select wire:model="serviceGroupServiceLeaderId" @disabled(auth()->user()->isServiceLeader())>
                        <option value="">{{ __('web_app.fallback.unassigned') }}</option>
                        @foreach ($serviceGroupServiceLeaderOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('serviceGroupServiceLeaderId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field">
                    <span>{{ __('service_groups.leader') }}</span>
                    <select wire:model="serviceGroupLeaderId" @disabled(! $editingServiceGroupId)>
                        <option value="">{{ __('web_app.fallback.unassigned') }}</option>
                        @foreach ($serviceGroupLeaderOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('serviceGroupLeaderId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field app-form-field-full"><span>{{ __('service_groups.description') }}</span><textarea wire:model="serviceGroupDescription" rows="4" placeholder="{{ __('web_app.forms.placeholders.service_group_description') }}"></textarea>@error('serviceGroupDescription') <small>{{ $message }}</small> @enderror</label>
                <div class="app-check-grid app-form-field-full"><label class="app-check-row"><input type="checkbox" wire:model="serviceGroupIsActive"><span>{{ __('web_app.forms.service_group.active') }}</span></label></div>
            </div>
            <div class="app-modal-actions">
                <button type="button" wire:click="closeServiceGroupForm" class="app-secondary-button">{{ __('web_app.actions.cancel') }}</button>
                <button type="button" wire:click="saveServiceGroup" wire:loading.attr="disabled" class="app-primary-button">{{ __('web_app.forms.service_group.save') }}</button>
            </div>
        </div>
    </section>
@endif
