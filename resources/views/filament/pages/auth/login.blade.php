<div class=" flex items-center justify-center">
    <div class="login-card flex flex-col md:flex-row overflow-hidden w-full max-w-4xl">

        {{-- ── الجانب الأيمن (RTL) / الأيسر (LTR): الديكور ── --}}
        <div
            class="sidebar-panel flex flex-row md:flex-col items-center justify-center md:w-2/5 lg:w-1/3 p-4 md:p-10 gap-3 md:gap-0 text-white text-center">

            {{-- أيقونة الصليب --}}
            <div class="md:mb-6 shrink-0" aria-hidden="true">
                <svg class="w-8 h-8 md:w-16 md:h-16" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false" aria-hidden="true">
                    <rect x="30" y="5" width="20" height="70" rx="6" fill="white" opacity="0.9" />
                    <rect x="5" y="28" width="70" height="20" rx="6" fill="white" opacity="0.9" />
                </svg>
            </div>

            {{-- اسم النظام --}}
            <p class="text-sm md:text-2xl font-bold leading-snug">
                {{ __('auth.system_name') }}
            </p>

            {{-- الآية — مخفية على الموبايل --}}
            <p class="hidden md:block text-sm opacity-70 mt-4 leading-relaxed max-w-xs">
                "{{ __('auth.verse') }}"
            </p>

            {{-- الذهب الدافئ في الأسفل — مخفي على الموبايل --}}
            <div class="hidden md:block mt-8 w-16 h-1 rounded-full login-gold-accent"></div>
        </div>

        {{-- ── جانب النموذج ── --}}
        <div class="flex flex-col justify-center flex-1 min-w-0 w-full p-4 sm:p-6 md:p-10">

            {{-- الـ Logo على الموبايل — محذوف لأن الـ sidebar بيظهر دايمًا --}}

            {{-- العنوان --}}
            <h1 class="text-2xl font-bold text-gray-900 mb-1">
                {{ __('auth.welcome_back') }}
            </h1>
            <p class="text-sm text-gray-500 mb-6">
                {{ __('auth.system_name') }}
            </p>

            {{-- Tabs --}}
            <div class="flex gap-2 mb-6 p-1 bg-gray-100 rounded-full w-fit" role="tablist" aria-label="{{ __('auth.login') }}">
                <button type="button" role="tab" aria-selected="{{ $activeTab === 'email' ? 'true' : 'false' }}" wire:click="switchTab('email')" class="tab-pill {{ $activeTab === 'email' ? 'active' : '' }}">
                    📧 {{ __('auth.by_email') }}
                </button>
                <button type="button" role="tab" aria-selected="{{ $activeTab === 'code' ? 'true' : 'false' }}" wire:click="switchTab('code')" class="tab-pill {{ $activeTab === 'code' ? 'active' : '' }}">
                    🔑 {{ __('auth.by_code') }}
                </button>
            </div>

            {{-- ── Tab 1: Email ── --}}
            @if ($activeTab === 'email')
                <form wire:submit="authenticate">
                    <div class="space-y-4">

                        <div>
                            <label class="input-label" for="login-email">{{ __('auth.email') }}</label>
                            <input type="email" wire:model="data.email" id="login-email" class="fi-input"
                                placeholder="admin@ministry.local" autocomplete="email" required />
                            @error('data.email')
                                <p class="error-msg" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="input-label" for="login-password">{{ __('auth.password_label') }}</label>
                            <div class="relative" x-data="{ showPw: false }">
                                <input :type="showPw ? 'text' : 'password'" wire:model="data.password" id="login-password" class="fi-input pe-12"
                                    autocomplete="current-password" required x-ref="passwordInput" />
                                <button type="button"
                                    @click="showPw = !showPw; $el.setAttribute('aria-pressed', showPw.toString())"
                                    aria-label="{{ __('auth.toggle_password') }}" aria-pressed="false"
                                    class="absolute inset-y-0 end-0 w-11 min-w-[44px] inline-flex items-center justify-center text-gray-400 hover:text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </button>
                            </div>
                            @error('data.password')
                                <p class="error-msg" role="alert">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs leading-relaxed text-gray-500">
                                {{ __('auth.locked_out_help') }}
                            </p>
                            <p class="mt-1 text-xs">
                                <a href="{{ route('password.request') }}"
                                    class="font-bold text-blue-600 hover:text-blue-800 transition">
                                    {{ __('auth.forgot_password') }}
                                </a>
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model="data.remember" id="remember" class="w-5 h-5 rounded" />
                            <label for="remember" class="text-sm text-gray-600 cursor-pointer">
                                {{ __('auth.remember_me') }}
                            </label>
                        </div>

                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('auth.sign_in') }}</span>
                            <span wire:loading>⏳ {{ __('auth.signing_in') }}</span>
                        </button>
                    </div>
                </form>
            @endif

            {{-- ── Tab 2: Code ── --}}
            @if ($activeTab === 'code')
                <form wire:submit="loginWithCode">
                    <div class="space-y-4">

                        {{-- كود الخادم (رقمي قديم أو KH-XXXX-XX) --}}
                        <div>
                            <label class="input-label mb-3" for="personal-code">
                                {{ __('auth.enter_code') }}
                            </label>

                            <input id="personal-code" type="text" wire:model="personalCode" dir="ltr"
                                autocomplete="one-time-code" maxlength="12" required
                                class="code-input w-full text-center tracking-widest"
                                placeholder="KH-XXXX-XX" />

                            <p class="text-xs text-gray-500 text-center mt-2">
                                {{ __('auth.code_hint') }}
                            </p>
                        </div>

                        {{-- كلمة المرور — عامل التحقق الثاني --}}
                        <div>
                            <label class="input-label mb-3" for="code-password">
                                {{ __('auth.password_label') }}
                            </label>

                            <input id="code-password" type="password" wire:model="codePassword" dir="ltr"
                                autocomplete="current-password" required
                                class="code-input w-full text-center" />

                            <p class="text-xs text-gray-500 text-center mt-2">
                                {{ __('auth.code_second_factor_hint') }}
                            </p>

                            @error('personalCode')
                                <p class="error-msg text-center mt-2">{{ $message }}</p>
                            @enderror

                            @error('codePassword')
                                <p class="error-msg text-center mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('auth.sign_in') }}</span>
                            <span wire:loading>⏳ {{ __('auth.signing_in') }}</span>
                        </button>
                    </div>
                </form>
            @endif

            {{-- Language Switcher --}}
            <div class="mt-6 text-center">
                <form method="POST"
                    action="{{ route('language.switch.guest', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
                    class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition min-h-[44px] px-4">
                        {{ app()->getLocale() === 'ar' ? __('auth.switch_to_english') : __('auth.switch_to_arabic') }}
                    </button>
                </form>
            </div>

            {{-- Registration Link --}}
            <div class="mt-4 text-center">
                <a href="{{ route('registration.public') }}"
                    class="text-sm text-blue-600 hover:text-blue-800 transition">
                    {{ __('auth.no_account_register') }}
                </a>
            </div>

            {{-- Continue with Google --}}
            @if (config('services.google.client_id'))
                <div class="mt-4 text-center">
                    <a href="{{ route('auth.google.redirect') }}"
                        class="inline-flex items-center justify-center gap-2 w-full sm:w-auto border border-gray-300 dark:border-gray-600 rounded-lg px-5 min-h-[44px] text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                            <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.5 6.1 29.5 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.2-.1-2.4-.4-3.5z"/>
                            <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.5 6.1 29.5 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
                            <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/>
                            <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.1 5.7l6.2 5.2C41 35.9 44 30.5 44 24c0-1.2-.1-2.4-.4-3.5z"/>
                        </svg>
                        {{ __('auth.continue_google') }}
                    </a>
                </div>
            @endif

        </div>
    </div>
</div>
