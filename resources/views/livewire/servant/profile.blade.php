<div class="px-4 pt-6 pb-32 lg:pb-10 space-y-5">

    {{-- Profile Card --}}
    <div class="s-card reveal-card rounded-3xl overflow-hidden">
        {{-- Header gradient --}}
        <div class="h-24 gradient-deep relative">
            <div class="absolute inset-0 opacity-20"
                 style="background-image: url(\"data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.3'/%3E%3C/svg%3E\");">
            </div>
        </div>

        {{-- Avatar --}}
        <div class="px-5 pb-5">
            <div class="flex items-end gap-4 -mt-8 mb-4">
                <x-ui.avatar
                    :name="$user->name"
                    :src="$user->profile_photo_url ?? null"
                    size="xl"
                    shape="square"
                    gradient="gold"
                    class="avatar-ring flex-shrink-0 relative z-10"
                />
                <div class="mb-1">
                    <h2 class="font-bold text-teal-900 text-lg">{{ $user->name }}</h2>
                    <span class="badge-pill badge-info text-xs">{{ $user->role->label() }}</span>
                </div>
            </div>

            {{-- Info Rows --}}
            <div class="space-y-3">
                @if($user->email)
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center flex-shrink-0">
                            <i class="ph ph-envelope text-teal-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">البريد الإلكتروني</p>
                            <p class="text-sm font-semibold text-teal-900" dir="ltr">{{ $user->email }}</p>
                        </div>
                    </div>
                @endif

                @if($user->phone)
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center flex-shrink-0">
                            <i class="ph ph-phone text-teal-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">رقم الهاتف</p>
                            <p class="text-sm font-semibold text-teal-900" dir="ltr">{{ $user->phone }}</p>
                        </div>
                    </div>
                @endif

                @if($user->personal_code)
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gold-100 flex items-center justify-center flex-shrink-0">
                            <i class="ph ph-key text-gold-700"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">الكود الشخصي</p>
                            <p class="text-lg font-bold text-teal-900 tracking-widest" style="font-family: var(--font-accent);">
                                {{ $user->personal_code }}
                            </p>
                        </div>
                    </div>
                @endif

                @if($user->serviceGroup)
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center flex-shrink-0">
                            <i class="ph ph-church text-teal-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">مجموعة الخدمة</p>
                            <p class="text-sm font-semibold text-teal-900">{{ $user->serviceGroup->name ?? '—' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Logout --}}
    <div class="reveal-card" style="animation-delay: 0.1s">
        <button wire:click="logout"
                wire:confirm="هل تريد تسجيل الخروج؟"
                class="w-full py-4 rounded-2xl flex items-center justify-center gap-2 font-bold text-base btn-ripple transition-all duration-200"
                style="background: linear-gradient(135deg, #E63946 0%, #C2323E 100%); color: white; box-shadow: 0 4px 16px rgba(230,57,70,0.25);">
            <i class="ph-bold ph-sign-out text-xl"></i>
            تسجيل الخروج
        </button>
    </div>

</div>
