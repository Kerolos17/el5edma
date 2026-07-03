<x-web-app.modal
    :show="$showUserForm"
    title="{{ $editingUserId ? __('web_app.forms.user.edit_title') : __('web_app.forms.user.create_title') }}"
    description="{{ __('web_app.forms.user.section') }}"
    close="closeUserForm"
>
    <div class="app-form-grid">
        <label class="app-form-field" for="user-name">
            <span>{{ __('users.name') }}</span>
            <input id="user-name" type="text" wire:model="userName" placeholder="{{ __('web_app.forms.placeholders.user_name') }}" aria-required="true" aria-invalid="{{ $errors->has('userName') ? 'true' : 'false' }}">
            @error('userName') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field" for="user-email">
            <span>{{ __('users.email') }}</span>
            <input id="user-email" type="email" wire:model="userEmail" placeholder="name@example.com" aria-required="{{ $editingUserId ? 'false' : 'true' }}" aria-invalid="{{ $errors->has('userEmail') ? 'true' : 'false' }}">
            @error('userEmail') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field" for="user-phone">
            <span>{{ __('users.phone') }}</span>
            <input id="user-phone" type="text" wire:model="userPhone" placeholder="{{ __('web_app.forms.placeholders.phone') }}" aria-invalid="{{ $errors->has('userPhone') ? 'true' : 'false' }}">
            @error('userPhone') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field" for="user-password">
            <span>{{ $editingUserId ? __('web_app.forms.user.new_password') : __('users.password') }}</span>
            <input id="user-password" type="password" wire:model="userPassword" placeholder="{{ $editingUserId ? __('web_app.forms.placeholders.password_unchanged') : __('web_app.forms.placeholders.password_min') }}" aria-required="{{ $editingUserId ? 'false' : 'true' }}" aria-invalid="{{ $errors->has('userPassword') ? 'true' : 'false' }}">
            @error('userPassword') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field" for="user-role">
            <span>{{ __('users.role') }}</span>
            <select id="user-role" wire:model.live="userRole" @disabled($editingUserId && $editingUserId === auth()->id()) aria-required="true" aria-invalid="{{ $errors->has('userRole') ? 'true' : 'false' }}">
                <option value="">{{ __('web_app.forms.select.role') }}</option>
                @foreach ($userRoleOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('userRole') <small>{{ $message }}</small> @enderror
            @if($editingUserId && $editingUserId === auth()->id())
                <small>{{ __('web_app.forms.user.cannot_edit_own_role') }}</small>
            @endif
        </label>
        <label class="app-form-field" for="user-service-group">
            <span>{{ __('users.service_group') }}</span>
            <select id="user-service-group" wire:model="userServiceGroupId" @disabled(in_array($userRole, ['super_admin', 'service_leader'], true) || ($editingUserId && $editingUserId === auth()->id())) aria-invalid="{{ $errors->has('userServiceGroupId') ? 'true' : 'false' }}">
                <option value="">{{ __('web_app.forms.select.group') }}</option>
                @foreach ($userServiceGroupOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            @error('userServiceGroupId') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field" for="user-locale">
            <span>{{ __('users.locale') }}</span>
            <select id="user-locale" wire:model="userLocale" aria-invalid="{{ $errors->has('userLocale') ? 'true' : 'false' }}">
                <option value="ar">{{ __('web_app.languages.ar') }}</option>
                <option value="en">{{ __('web_app.languages.en') }}</option>
            </select>
            @error('userLocale') <small>{{ $message }}</small> @enderror
        </label>
        @if (! $editingUserId || $editingUserId !== auth()->id())
            <div class="app-check-grid app-form-field-full">
                <label class="app-check-row" for="user-is-active">
                    <input id="user-is-active" type="checkbox" wire:model="userIsActive" aria-invalid="{{ $errors->has('userIsActive') ? 'true' : 'false' }}">
                    <span>{{ __('web_app.forms.user.active') }}</span>
                </label>
            </div>
        @endif
    </div>
    <x-slot:actions>
        <button type="button" wire:click="closeUserForm" class="app-secondary-button">{{ __('web_app.actions.cancel') }}</button>
        <button type="button" wire:click="saveUser" wire:loading.attr="disabled" class="app-primary-button">
            <span wire:loading.remove wire:target="saveUser">{{ __('web_app.forms.user.save') }}</span>
            <span wire:loading wire:target="saveUser">{{ __('web_app.actions.saving') }}</span>
        </button>
    </x-slot:actions>
</x-web-app.modal>
