<section class="app-page-stack">
    <x-slot:title>{{ __('web_app.profile.title') }}</x-slot:title>

    <div class="app-page-actions">
        <a href="{{ route('app.dashboard') }}" wire:navigate class="app-secondary-button">
            <i class="ph ph-squares-four" aria-hidden="true"></i>
            {{ __('web_app.actions.dashboard') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Personal Info Form --}}
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.profile.personal_info') }}</p>
                        <h3>{{ __('web_app.profile.account_details') }}</h3>
                    </div>
                </div>

                <form wire:submit="saveProfile">
                    <div class="app-form-grid">
                        <div class="app-form-field">
                            <span>{{ __('web_app.profile.name') }}</span>
                            <input type="text" wire:model="name" required maxlength="255">
                            @error('name') <small>{{ $message }}</small> @enderror
                        </div>

                        <div class="app-form-field">
                            <span>{{ __('web_app.profile.email') }}</span>
                            <input type="email" wire:model="email" disabled readonly>
                            <em>{{ __('web_app.profile.email_hint') }}</em>
                        </div>

                        <div class="app-form-field">
                            <span>{{ __('web_app.profile.phone') }}</span>
                            <input type="tel" wire:model="phone" maxlength="20">
                            @error('phone') <small>{{ $message }}</small> @enderror
                        </div>

                        <div class="app-form-field">
                            <span>{{ __('web_app.profile.language') }}</span>
                            <select wire:model="locale">
                                <option value="ar">{{ __('web_app.profile.arabic') }}</option>
                                <option value="en">{{ __('web_app.profile.english') }}</option>
                            </select>
                            <em>{{ __('web_app.profile.language_hint') }}</em>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="app-primary-button">
                            <i class="ph ph-check" aria-hidden="true"></i>
                            {{ __('web_app.actions.save') }}
                        </button>
                    </div>
                </form>
            </section>

            {{-- Account Info --}}
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.profile.account_info') }}</p>
                        <h3>{{ __('web_app.profile.role_details') }}</h3>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ __('web_app.profile.role') }}</span>
                        <p class="mt-1">
                            <span class="app-status-pill">{{ $user->role->label() }}</span>
                        </p>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ __('web_app.profile.service_group') }}</span>
                        <p class="mt-1 text-sm font-bold">{{ $user->serviceGroup?->name ?? __('web_app.fallback.unassigned') }}</p>
                    </div>
                    @if ($user->personal_code)
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ __('web_app.profile.personal_code') }}</span>
                            <p class="mt-1 font-mono text-sm">{{ $user->personal_code }}</p>
                        </div>
                    @endif
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ __('web_app.profile.member_since') }}</span>
                        <p class="mt-1 text-sm font-bold">{{ $user->created_at?->format('Y-m-d') ?? __('web_app.fallback.unknown') }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ __('web_app.profile.last_login') }}</span>
                        <p class="mt-1 text-sm font-bold">{{ $user->last_login_at?->format('Y-m-d H:i') ?? __('web_app.fallback.never') }}</p>
                    </div>
                </div>
            </section>

            {{-- Password Change --}}
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.profile.security') }}</p>
                        <h3>{{ __('web_app.profile.change_password') }}</h3>
                    </div>
                </div>

                <form wire:submit="updatePassword">
                    <div class="app-form-grid">
                        <div class="app-form-field">
                            <span>{{ __('web_app.profile.current_password') }}</span>
                            <input type="password" wire:model="currentPassword" required>
                            @error('currentPassword') <small>{{ $message }}</small> @enderror
                        </div>

                        <div class="app-form-field">
                            <span>{{ __('web_app.profile.new_password') }}</span>
                            <input type="password" wire:model="newPassword" required minlength="8">
                            <em>{{ __('web_app.profile.password_hint') }}</em>
                            @error('newPassword') <small>{{ $message }}</small> @enderror
                        </div>

                        <div class="app-form-field">
                            <span>{{ __('web_app.profile.new_password_confirmation') }}</span>
                            <input type="password" wire:model="newPasswordConfirmation" required minlength="8">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="app-primary-button">
                            <i class="ph ph-check" aria-hidden="true"></i>
                            {{ __('web_app.actions.save') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>

        {{-- Photo Card --}}
        <div>
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.profile.photo_section') }}</p>
                        <h3>{{ __('web_app.profile.profile_photo') }}</h3>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-4 py-4">
                    <div class="w-28 h-28 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center ring-4 ring-gray-100 dark:ring-gray-700">
                        @if ($user->profile_photo_url)
                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <span class="text-3xl font-bold text-gray-400 dark:text-gray-500">
                                {{ mb_substr($user->name, 0, 1) }}
                            </span>
                        @endif
                    </div>

                    @if ($showPhotoForm)
                        <div class="w-full space-y-3">
                            <input type="file" wire:model="newPhoto" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                   class="block w-full text-sm text-gray-500 file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            @error('newPhoto') <small class="text-rose-600 text-xs font-bold">{{ $message }}</small> @enderror

                            @if ($newPhoto)
                                <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600">
                                    <img src="{{ $newPhoto->temporaryUrl() }}" alt="Preview" class="w-full h-32 object-cover">
                                </div>
                            @endif

                            @if ($user->profile_photo)
                                <button type="button" wire:click="removePhoto" class="app-link-inline app-link-danger">
                                    <i class="ph ph-trash" aria-hidden="true"></i>
                                    {{ __('web_app.profile.remove_photo') }}
                                </button>
                            @endif
                        </div>
                    @else
                        <button type="button" wire:click="$set('showPhotoForm', true)" class="app-secondary-button">
                            <i class="ph ph-camera" aria-hidden="true"></i>
                            {{ __('web_app.profile.change_photo') }}
                        </button>
                    @endif
                </div>
            </section>
        </div>
    </div>
</section>
