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
                <div class="flex-shrink-0 relative z-10">
                    <x-ui.avatar
                        :name="$user->name"
                        :src="$user->profile_photo_url ?? null"
                        size="xl"
                        shape="square"
                        gradient="gold"
                        class="avatar-ring"
                    />
                    <div class="flex gap-1 mt-2" role="group" aria-label="إدارة الصورة الشخصية">
                        <label for="profile-photo-input"
                            class="flex-1 min-h-[40px] px-3 rounded-xl bg-teal-600 text-white text-xs font-bold flex items-center justify-center gap-1 cursor-pointer active:scale-95 transition">
                            <i class="ph ph-camera" aria-hidden="true"></i>
                            {{ $user->profile_photo ? 'تغيير' : 'إضافة صورة' }}
                        </label>
                        @if ($user->profile_photo)
                            <button type="button" wire:click="removePhoto"
                                wire:confirm="هل تريد حذف صورتك الشخصية؟"
                                class="min-h-[40px] px-3 rounded-xl bg-gray-100 text-gray-600 text-xs font-bold flex items-center justify-center active:scale-95 transition">
                                <i class="ph ph-trash" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>
                    <input id="profile-photo-input" type="file" accept="image/jpeg,image/png,image/webp"
                        wire:model="newPhoto" class="sr-only"
                        aria-label="اختيار صورة شخصية" />
                    @error('newPhoto')
                        <p class="text-[11px] text-red-600 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-1">
                    <h2 class="font-bold text-teal-900 text-lg">{{ $user->name }}</h2>
                    <span class="badge-pill badge-info text-xs">{{ $user->role->label() }}</span>
                </div>
            </div>
            <p class="text-[11px] text-gray-400 -mt-2 mb-3">
                الصورة تُضغط تلقائيًا قبل الرفع ولا تتجاوز 512px — تظهر فقط كصورة حسابك.
            </p>

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

    <script>
        // ضغط الصورة في المتصفح قبل رفعها: أقصى بعد 1600px بترميز JPEG 80% —
        // صورة كاميرا نموذجية تنزل من ~4MB إلى أقل من 300KB.
        (function () {
            const input = document.getElementById('profile-photo-input');
            if (!input || input.dataset.compressorBound) return;
            input.dataset.compressorBound = '1';

            input.addEventListener('change', async () => {
                const file = input.files && input.files[0];
                if (!file || !file.type.startsWith('image/')) return;
                if (file.size <= 250 * 1024) return; // small enough already

                try {
                    const bitmap = await createImageBitmap(file);
                    const maxSide = 1600;
                    const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height));
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.round(bitmap.width * scale);
                    canvas.height = Math.round(bitmap.height * scale);
                    canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);

                    const blob = await new Promise((resolve) => {
                        canvas.toBlob(
                            (b) => resolve(b && b.size < file.size ? b : null),
                            'image/jpeg',
                            0.8,
                        );
                    });

                    if (blob) {
                        const compressed = new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' });
                        const transfer = new DataTransfer();
                        transfer.items.add(compressed);
                        input.files = transfer.files;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                } catch (error) {
                    // Compression is best-effort — the original upload still works.
                }
            });
        })();
    </script>

</div>
