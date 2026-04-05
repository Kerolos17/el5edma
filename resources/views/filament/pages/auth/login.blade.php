<div class=" flex items-center justify-center">
    <div class="login-card flex flex-col md:flex-row overflow-hidden w-full max-w-4xl">

        {{-- ── الجانب الأيمن (RTL) / الأيسر (LTR): الديكور ── --}}
        <div
            class="sidebar-panel flex flex-row md:flex-col items-center justify-center md:w-2/5 lg:w-1/3 p-4 md:p-10 gap-3 md:gap-0 text-white text-center">

            {{-- أيقونة الصليب --}}
            <div class="md:mb-6 shrink-0">
                <svg class="w-8 h-8 md:w-16 md:h-16" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="30" y="5" width="20" height="70" rx="6" fill="white" opacity="0.9" />
                    <rect x="5" y="28" width="70" height="20" rx="6" fill="white" opacity="0.9" />
                </svg>
            </div>

            {{-- اسم النظام --}}
            <h2 class="text-sm md:text-2xl font-bold leading-snug">
                {{ __('auth.system_name') }}
            </h2>

            {{-- الآية — مخفية على الموبايل --}}
            <p class="hidden md:block text-sm opacity-70 mt-4 leading-relaxed max-w-xs">
                "{{ __('auth.verse') }}"
            </p>

            {{-- الذهب الدافئ في الأسفل — مخفي على الموبايل --}}
            <div class="hidden md:block mt-8 w-16 h-1 rounded-full login-gold-accent"></div>
        </div>

        {{-- ── جانب النموذج ── --}}
        <div class="flex flex-col justify-center flex-1 min-w-[280px] p-6 md:p-10">

            {{-- الـ Logo على الموبايل — محذوف لأن الـ sidebar بيظهر دايمًا --}}

            {{-- العنوان --}}
            <h1 class="text-2xl font-bold text-gray-900 mb-1">
                {{ __('auth.welcome_back') }}
            </h1>
            <p class="text-sm text-gray-500 mb-6">
                {{ __('auth.system_name') }}
            </p>

            {{-- Tabs --}}
            <div class="flex gap-2 mb-6 p-1 bg-gray-100 rounded-full w-fit">
                <button wire:click="switchTab('email')" class="tab-pill {{ $activeTab === 'email' ? 'active' : '' }}">
                    📧 {{ __('auth.by_email') }}
                </button>
                <button wire:click="switchTab('code')" class="tab-pill {{ $activeTab === 'code' ? 'active' : '' }}">
                    🔑 {{ __('auth.by_code') }}
                </button>
            </div>

            {{-- ── Tab 1: Email ── --}}
            @if ($activeTab === 'email')
                <form wire:submit="authenticate">
                    <div class="space-y-4">

                        <div>
                            <label class="input-label">{{ __('auth.email') }}</label>
                            <input type="email" wire:model="data.email" class="fi-input"
                                placeholder="admin@ministry.local" autocomplete="email" required />
                            @error('data.email')
                                <p class="error-msg">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="input-label">{{ __('auth.password_label') }}</label>
                            <input type="password" wire:model="data.password" class="fi-input"
                                autocomplete="current-password" required />
                            @error('data.password')
                                <p class="error-msg">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model="data.remember" id="remember" class="rounded" />
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
                            <label class="input-label mb-3">
                                {{ __('auth.enter_code') }}
                            </label>

                            <div class="flex gap-2 justify-center my-4" dir="ltr" x-data="{
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
