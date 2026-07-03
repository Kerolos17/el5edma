<x-web-app.modal
    :show="$showServiceGroupForm"
    title="{{ $editingServiceGroupId ? __('web_app.forms.service_group.edit_title') : __('web_app.forms.service_group.create_title') }}"
    description="{{ __('web_app.forms.service_group.section') }}"
    close="closeServiceGroupForm"
>
    <div class="app-form-grid">
        <label class="app-form-field" for="service-group-name">
            <span>{{ __('service_groups.name') }}</span>
            <input id="service-group-name" type="text" wire:model="serviceGroupName" placeholder="{{ __('web_app.forms.placeholders.service_group_name') }}" aria-required="true" aria-invalid="{{ $errors->has('serviceGroupName') ? 'true' : 'false' }}">
            @error('serviceGroupName') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field" for="service-group-service-leader">
            <span>{{ __('service_groups.service_leader') }}</span>
            <select id="service-group-service-leader" wire:model="serviceGroupServiceLeaderId" @disabled(auth()->user()->isServiceLeader()) aria-invalid="{{ $errors->has('serviceGroupServiceLeaderId') ? 'true' : 'false' }}">
                <option value="">{{ __('web_app.fallback.unassigned') }}</option>
                @foreach ($serviceGroupServiceLeaderOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            @error('serviceGroupServiceLeaderId') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field" for="service-group-leader">
            <span>{{ __('service_groups.leader') }}</span>
            <select id="service-group-leader" wire:model="serviceGroupLeaderId" @disabled(! $editingServiceGroupId) aria-invalid="{{ $errors->has('serviceGroupLeaderId') ? 'true' : 'false' }}">
                <option value="">{{ __('web_app.fallback.unassigned') }}</option>
                @foreach ($serviceGroupLeaderOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            @error('serviceGroupLeaderId') <small>{{ $message }}</small> @enderror
            @if(! $editingServiceGroupId)
                <small>{{ __('web_app.forms.service_group.leader_assigned_after_creation') }}</small>
            @endif
        </label>
        <label class="app-form-field app-form-field-full" for="service-group-description">
            <span>{{ __('service_groups.description') }}</span>
            <textarea id="service-group-description" wire:model="serviceGroupDescription" rows="4" placeholder="{{ __('web_app.forms.placeholders.service_group_description') }}" aria-invalid="{{ $errors->has('serviceGroupDescription') ? 'true' : 'false' }}"></textarea>
            @error('serviceGroupDescription') <small>{{ $message }}</small> @enderror
        </label>
        <div class="app-check-grid app-form-field-full">
            <label class="app-check-row" for="service-group-is-active">
                <input id="service-group-is-active" type="checkbox" wire:model="serviceGroupIsActive" aria-invalid="{{ $errors->has('serviceGroupIsActive') ? 'true' : 'false' }}">
                <span>{{ __('web_app.forms.service_group.active') }}</span>
            </label>
            @error('serviceGroupIsActive') <small>{{ $message }}</small> @enderror
        </div>
    </div>
    <x-slot:actions>
        <button type="button" wire:click="closeServiceGroupForm" class="app-secondary-button">{{ __('web_app.actions.cancel') }}</button>
        <button type="button" wire:click="saveServiceGroup" wire:loading.attr="disabled" class="app-primary-button">
            <span wire:loading.remove wire:target="saveServiceGroup">{{ __('web_app.forms.service_group.save') }}</span>
            <span wire:loading wire:target="saveServiceGroup">{{ __('web_app.actions.saving') }}</span>
        </button>
    </x-slot:actions>
</x-web-app.modal>
