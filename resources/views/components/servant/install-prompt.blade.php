{{--
    PWA Install Prompt — يظهر مرة واحدة بعد أول تصفح ناجح.
    يستمع لـ beforeinstallprompt ويعرض bottom-sheet مخصص.
    يُخفى نهائياً إذا رفض المستخدم أو بعد التثبيت.
--}}
<div
    x-data="{
        show: false,
        deferredPrompt: null,
        init() {
            if (localStorage.getItem('pwa-install-dismissed')) return;

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                // أظهر بعد ثانيتين لإتاحة الوقت لتحميل الصفحة
                setTimeout(() => { this.show = true; }, 2000);
            });

            window.addEventListener('appinstalled', () => {
                this.show = false;
                this.deferredPrompt = null;
                localStorage.setItem('pwa-install-dismissed', '1');
            });
        },
        async install() {
            if (!this.deferredPrompt) return;
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                localStorage.setItem('pwa-install-dismissed', '1');
            }
            this.show = false;
            this.deferredPrompt = null;
        },
        dismiss() {
            this.show = false;
            localStorage.setItem('pwa-install-dismissed', '1');
        }
    }"
    x-cloak>

    {{-- Overlay --}}
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="dismiss"
        class="fixed inset-0 z-[170]"
        style="background: rgba(0,61,66,0.4); backdrop-filter: blur(4px);"
        aria-hidden="true">
    </div>

    {{-- Bottom Sheet --}}
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-400"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-250"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="fixed bottom-0 left-0 right-0 z-[180] rounded-t-[28px] p-6"
        style="background: #FFFBF7; padding-bottom: max(1.5rem, env(safe-area-inset-bottom));"
        role="dialog"
        aria-modal="true"
        aria-label="تثبيت التطبيق">

        {{-- Drag handle --}}
        <div class="flex justify-center mb-5" aria-hidden="true">
            <div class="w-10 h-1 rounded-full bg-gray-200"></div>
        </div>

        {{-- App icon + info --}}
        <div class="flex items-center gap-4 mb-5">
            <div class="w-16 h-16 rounded-2xl overflow-hidden flex-shrink-0 shadow-lg">
                <img src="/icons/icon-192x192.png" alt="أيقونة التطبيق" class="w-full h-full object-cover">
            </div>
            <div>
                <h3 class="font-bold text-teal-900 text-lg" style="font-family: var(--font-display);">
                    {{ config('app.name') }}
                </h3>
                <p class="text-sm text-gray-500 mt-0.5">ثبّت التطبيق للوصول السريع بدون متصفح</p>
            </div>
        </div>

        {{-- Benefits --}}
        <div class="space-y-2 mb-6">
            @foreach([
                ['ph-fill ph-lightning', 'تشغيل أسرع بدون تحميل الصفحة'],
                ['ph-fill ph-wifi-slash', 'عمل أوفلاين وحفظ الزيارات'],
                ['ph-fill ph-bell', 'إشعارات فورية للحالات الحرجة'],
            ] as [$icon, $text])
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background: rgba(0,109,119,0.08)">
                        <i class="{{ $icon }} text-sm" style="color: #006D77" aria-hidden="true"></i>
                    </div>
                    <p class="text-sm text-gray-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <button
                @click="dismiss"
                class="flex-1 py-3 rounded-2xl border-2 border-gray-200 text-gray-500 font-semibold text-sm hover:bg-gray-50 transition-colors">
                لاحقاً
            </button>
            <button
                @click="install"
                class="flex-[2] py-3 rounded-2xl font-bold text-sm text-white btn-ripple"
                style="background: linear-gradient(135deg, #006D77 0%, #003942 100%); box-shadow: 0 4px 16px rgba(0,109,119,0.3);">
                <i class="ph-bold ph-download-simple ml-1" aria-hidden="true"></i>
                تثبيت التطبيق
            </button>
        </div>
    </div>
</div>
