<div class="px-4 pt-6 pb-32 lg:pb-10 space-y-5">

    <h1 class="sr-only">حسابي</h1>

    {{-- Profile Card --}}
    <div class="s-card rounded-3xl overflow-hidden">
        {{-- Header gradient --}}
        <div class="h-24 gradient-deep relative">
            <div class="profile-card__noise absolute inset-0 opacity-20"></div>
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
                            <p class="accent-font text-lg font-bold text-teal-900 tracking-widest">
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
    <div>
        <button wire:click="logout"
                wire:confirm="هل تريد تسجيل الخروج؟"
                class="logout-btn w-full py-4 rounded-2xl flex items-center justify-center gap-2 font-bold text-base btn-ripple transition-all duration-200">
            <i class="ph-bold ph-sign-out text-xl"></i>
            تسجيل الخروج
        </button>
    </div>

</div>
