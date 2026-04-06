<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Profile Photo --}}
        <div class="p-6 bg-[var(--bg-surface)] rounded-xl shadow-sm border border-[var(--color-border)]">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                {{ __('users.profile_photo') }}
            </h2>
            <div class="flex items-center gap-6">
                <div class="shrink-0">
                    @if(auth()->user()->profile_photo)
                        <img
                            src="{{ auth()->user()->profile_photo_url }}"
                            alt="{{ auth()->user()->name }}"
                            class="w-24 h-24 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600"
                        />
                    @else
                        <div class="w-24 h-24 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                            <x-heroicon-o-user class="w-12 h-12 text-gray-400 dark:text-gray-500" />
                        </div>
                    @endif
                </div>
                <div class="space-y-2">
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        wire:model="newPhoto"
                        id="profile-photo-input"
                        class="hidden"
                    />
                    <div wire:loading wire:target="newPhoto" class="text-sm text-primary-600 dark:text-primary-400">
                        {{ __('users.uploading') }}...
                    </div>
                    @error('newPhoto')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <div class="flex gap-2">
                        <button
                            type="button"
                            onclick="document.getElementById('profile-photo-input').click()"
                            class="px-4 py-2 rounded-lg text-sm font-medium bg-primary-600 text-white hover:bg-primary-700 transition"
                            wire:loading.attr="disabled"
                            wire:target="newPhoto"
                        >
                            {{ __('users.upload_photo') }}
                        </button>
                        @if(auth()->user()->profile_photo)
                            <button
                                type="button"
                                wire:click="removePhoto"
                                wire:confirm="{{ __('users.remove_photo_confirm') }}"
                                class="px-4 py-2 rounded-lg text-sm font-medium bg-red-600 text-white hover:bg-red-700 transition"
                            >
                                {{ __('users.remove_photo') }}
                            </button>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('users.profile_photo_helper') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- User Info --}}
        <div class="p-6 bg-[var(--bg-surface)] rounded-xl shadow-sm border border-[var(--color-border)]">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                {{ __('users.my_info') }}
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('users.name') }}</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ auth()->user()->name }}</span>
                </div>
                <div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('users.email') }}</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ auth()->user()->email }}</span>
                </div>
                <div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('users.role') }}</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ auth()->user()->role->label() }}</span>
                </div>
                @if(auth()->user()->phone)
                <div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('users.phone') }}</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ auth()->user()->phone }}</span>
                </div>
                @endif
                @if(auth()->user()->serviceGroup)
                <div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('users.service_group') }}</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ auth()->user()->serviceGroup->name }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Language Preference --}}
        <div class="p-6 bg-[var(--bg-surface)] rounded-xl shadow-sm border border-[var(--color-border)]">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                {{ __('users.locale') }}
            </h2>
            <div class="flex flex-wrap gap-3 items-center">
                <button
                    wire:click="$set('locale', 'ar')"
                    @class([
                        'px-5 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-primary-600 text-white border-primary-600' => $locale === 'ar',
                        'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600' => $locale !== 'ar',
                    ])>
                    {{ __('users.arabic') }}
                </button>
                <button
                    wire:click="$set('locale', 'en')"
                    @class([
                        'px-5 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-primary-600 text-white border-primary-600' => $locale === 'en',
                        'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600' => $locale !== 'en',
                    ])>
                    {{ __('users.english') }}
                </button>
                <button
                    wire:click="saveLocale"
                    class="px-5 py-2 rounded-lg text-sm font-medium bg-primary-600 text-white hover:bg-primary-700 transition">
                    {{ __('users.save_locale') }}
                </button>
            </div>
        </div>

    </div>
</x-filament-panels::page>
