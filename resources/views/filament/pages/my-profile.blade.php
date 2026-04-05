<x-filament-panels::page>
    <div class="space-y-6">

        {{-- بيانات المستخدم --}}
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
                @if(auth()->user()->serviceGroup)
                <div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('users.service_group') }}</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ auth()->user()->serviceGroup->name }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- تفضيل اللغة --}}
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
