@if ($showUserForm)
    <div class="app-modal-backdrop" wire:click="closeUserForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="{{ __('web_app.forms.user.manage') }}">
        <div class="app-modal-panel" tabindex="-1">
            <div class="app-modal-header">
                <div><p class="app-section-label">{{ __('web_app.forms.user.section') }}</p><h3>{{ $editingUserId ? __('web_app.forms.user.edit_title') : __('web_app.forms.user.create_title') }}</h3></div>
                <button type="button" wire:click="closeUserForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="app-form-grid">
                <label class="app-form-field"><span>{{ __('users.name') }}</span><input type="text" wire:model="userName" placeholder="{{ __('web_app.forms.placeholders.user_name') }}">@error('userName') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field"><span>{{ __('users.email') }}</span><input type="email" wire:model="userEmail" placeholder="name@example.com">@error('userEmail') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field"><span>{{ __('users.phone') }}</span><input type="text" wire:model="userPhone" placeholder="{{ __('web_app.forms.placeholders.phone') }}">@error('userPhone') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field"><span>{{ $editingUserId ? __('web_app.forms.user.new_password') : __('users.password') }}</span><input type="password" wire:model="userPassword" placeholder="{{ $editingUserId ? __('web_app.forms.placeholders.password_unchanged') : __('web_app.forms.placeholders.password_min') }}">@error('userPassword') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field">
                    <span>{{ __('users.role') }}</span>
                    <select wire:model.live="userRole" @disabled($editingUserId && $editingUserId === auth()->id())>
                        <option value="">{{ __('web_app.forms.select.role') }}</option>
                        @foreach ($userRoleOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('userRole') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field">
                    <span>{{ __('users.service_group') }}</span>
                    <select wire:model="userServiceGroupId" @disabled(in_array($userRole, ['super_admin', 'service_leader'], true) || ($editingUserId && $editingUserId === auth()->id()))>
                        <option value="">{{ __('web_app.forms.select.group') }}</option>
                        @foreach ($userServiceGroupOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('userServiceGroupId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field"><span>{{ __('users.locale') }}</span><select wire:model="userLocale"><option value="ar">{{ __('web_app.languages.ar') }}</option><option value="en">{{ __('web_app.languages.en') }}</option></select>@error('userLocale') <small>{{ $message }}</small> @enderror</label>
                @if (! $editingUserId || $editingUserId !== auth()->id())
                    <div class="app-check-grid app-form-field-full"><label class="app-check-row"><input type="checkbox" wire:model="userIsActive"><span>{{ __('web_app.forms.user.active') }}</span></label></div>
                @endif
            </div>
            <div class="app-modal-actions">
                <button type="button" wire:click="closeUserForm" class="app-secondary-button">{{ __('web_app.actions.cancel') }}</button>
                <button type="button" wire:click="saveUser" wire:loading.attr="disabled" class="app-primary-button">{{ __('web_app.forms.user.save') }}</button>
            </div>
        </div>
    </section>
@endif
