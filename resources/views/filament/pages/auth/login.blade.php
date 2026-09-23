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
                            <p class="mt-2 text-xs leading-relaxed text-gray-400">
                                {{ __('auth.locked_out_help') }}
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

                        {{-- مربعات الكود --}}
                        <div>
                            <label class="input-label mb-3" id="code-group-label">
                                {{ __('auth.enter_code') }}
                            </label>

                            <div class="flex gap-2 justify-center my-4" dir="ltr" role="group" aria-labelledby="code-group-label" x-data="{
                                code: ['', '', '', '', '', ''],
                                handleInput(index, event) {
                                    const val = event.target.value.replace(/\D/g, '');
                                    if (val.length > 1) {
                                        const digits = val.split('').slice(0, 6);
                                        digits.forEach((d, i) => {
                                            if (this.code[i] !== undefined) this.code[i] = d;
                                        });
                                        this.$nextTick(() => {
                                            const last = Math.min(digits.length, 5);
                                            this.$refs['box_' + last]?.focus();
                                        });
                                    } else {
                                        this.code[index] = val;
                                        if (val && index < 5) {
                                            this.$nextTick(() => this.$refs['box_' + (index + 1)]?.focus());
                                        }
                                    }
                                    this.$wire.set('personalCode', this.code.join(''));
                                },
                                handleKeydown(index, event) {
                                    if (event.key === 'Backspace' && !this.code[index] && index > 0) {
                                        this.$nextTick(() => this.$refs['box_' + (index - 1)]?.focus());
                                    }
                                },
                                handlePaste(event) {
                                    event.preventDefault();
                                    const paste = (event.clipboardData || window.clipboardData)
                                        .getData('text').replace(/\D/g, '').slice(0, 6);
                                    paste.split('').forEach((d, i) => {
                                        if (this.code[i] !== undefined) this.code[i] = d;
                                    });
                                    this.$nextTick(() => {
                                        const last = Math.min(paste.length, 5);
                                        this.$refs['box_' + last]?.focus();
                                    });
                                    this.$wire.set('personalCode', this.code.join(''));
                                }
                            }">
                                @foreach (range(0, 5) as $i)
                                    <input x-ref="box_{{ $i }}" type="text" inputmode="numeric"
                                        maxlength="1" class="code-input" x-model="code[{{ $i }}]"
                                        aria-label="{{ __('auth.code_digit', ['position' => $i + 1]) }}"
                                        @input="handleInput({{ $i }}, $event)"
                                        @keydown="handleKeydown({{ $i }}, $event)"
                                        @paste="handlePaste($event)" />
                                @endforeach
                            </div>

                            <p class="text-xs text-gray-400 text-center mt-2">
                                {{ __('auth.code_hint') }}
                            </p>

                            @error('personalCode')
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
                    <button type="submit" class="text-sm text-gray-400 hover:text-gray-600 transition">
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

        </div>
    </div>
</div>
