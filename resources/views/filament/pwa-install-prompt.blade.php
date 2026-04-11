{{-- Android / Desktop: uses beforeinstallprompt event --}}
<div x-data="pwaInstallPrompt()" x-show="showPrompt" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-4 sm:translate-y-0 sm:scale-95"
    x-transition:enter-end="opacity-100 transform translate-y-0 sm:scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0 sm:scale-100"
    x-transition:leave-end="opacity-0 transform translate-y-4 sm:translate-y-0 sm:scale-95"
    class="fixed bottom-0 left-0 right-0 z-100 pb-safe sm:bottom-6 sm:left-auto sm:right-6 sm:w-96"
    style="display: none;">

    <div
        class="bg-white dark:bg-gray-800 border-t sm:border border-gray-200 dark:border-gray-700 p-4 sm:rounded-2xl shadow-[0_-8px_30px_rgb(0,0,0,0.12)] sm:shadow-2xl flex items-center gap-4">

        <div class="shrink-0">
            <img src="{{ asset('icons/icon-72x72.png') }}"
                class="w-14 h-14 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700" alt="App Icon">
        </div>

        <div class="flex-1">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1 leading-tight">
                تثبيت التطبيق
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 leading-relaxed">
                أضف نظام الافتقاد إلى شاشتك الرئيسية للوصول السريع والإشعارات الفورية.
            </p>
            <div class="flex gap-2">
                <button @click="install()"
                    class="flex-1 bg-primary-600 hover:bg-primary-500 text-white text-sm font-semibold py-2 px-4 rounded-lg transition-colors min-h-[44px]">
                    تثبيت
                </button>
                <button @click="dismiss()"
                    class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors min-h-[44px]">
                    ربما لاحقاً
                </button>
            </div>
        </div>
    </div>
</div>

{{-- iOS Safari: beforeinstallprompt is not supported — show manual guide instead --}}
<div x-data="iosInstallHint()" x-show="showIosHint" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-4"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-4"
    class="fixed bottom-0 left-0 right-0 z-100 pb-safe sm:bottom-6 sm:left-auto sm:right-6 sm:w-96"
    style="display: none;">

    <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4 sm:rounded-2xl shadow-[0_-8px_30px_rgb(0,0,0,0.12)] sm:shadow-2xl">

        <div class="flex items-start gap-3 mb-4">
            <img src="{{ asset('icons/icon-72x72.png') }}"
                class="w-12 h-12 rounded-xl shrink-0 border border-gray-100 dark:border-gray-700" alt="App Icon">
            <div>
                <h3 class="font-bold text-gray-900 dark:text-white text-sm leading-tight">
                    تثبيت التطبيق على iPhone
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    أضف النظام لشاشتك الرئيسية للوصول السريع
                </p>
            </div>
        </div>

        <ol class="space-y-3 mb-4" dir="rtl">
            <li class="flex items-center gap-3 text-xs text-gray-700 dark:text-gray-300">
                <span class="w-6 h-6 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-full text-xs flex items-center justify-center font-bold shrink-0">١</span>
                اضغط على زر المشاركة
                <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-gray-300 font-bold">↑</span>
                في أسفل Safari
            </li>
            <li class="flex items-center gap-3 text-xs text-gray-700 dark:text-gray-300">
                <span class="w-6 h-6 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-full text-xs flex items-center justify-center font-bold shrink-0">٢</span>
                اختر <strong>"إضافة إلى الشاشة الرئيسية"</strong>
            </li>
            <li class="flex items-center gap-3 text-xs text-gray-700 dark:text-gray-300">
                <span class="w-6 h-6 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-full text-xs flex items-center justify-center font-bold shrink-0">٣</span>
                اضغط <strong>"إضافة"</strong> في الأعلى للتأكيد
            </li>
        </ol>

        <button @click="dismiss()"
            class="w-full text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors min-h-11">
            حسناً، فهمت
        </button>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {

        // --- Android / Desktop prompt ---
        Alpine.data('pwaInstallPrompt', () => ({
            showPrompt: false,
            deferredPrompt: null,

            init() {
                try {
                    if (localStorage.getItem('pwaPromptDismissed')) return;
                } catch (e) {}

                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.deferredPrompt = e;
                    setTimeout(() => { this.showPrompt = true; }, 3000);
                });

                window.addEventListener('appinstalled', () => {
                    this.showPrompt = false;
                    this.deferredPrompt = null;
                });
            },

            async install() {
                this.showPrompt = false;
                if (!this.deferredPrompt) return;
                this.deferredPrompt.prompt();
                await this.deferredPrompt.userChoice;
                this.deferredPrompt = null;
            },

            dismiss() {
                this.showPrompt = false;
                try { localStorage.setItem('pwaPromptDismissed', '1'); } catch (e) {}
            }
        }));

        // --- iOS Safari manual guide ---
        Alpine.data('iosInstallHint', () => ({
            showIosHint: false,

            init() {
                const isIos        = /iphone|ipad|ipod/i.test(navigator.userAgent);
                const isStandalone = window.navigator.standalone === true;
                const dismissed    = (() => { try { return localStorage.getItem('iosHintDismissed'); } catch { return false; } })();

                if (isIos && !isStandalone && !dismissed) {
                    setTimeout(() => { this.showIosHint = true; }, 5000);
                }
            },

            dismiss() {
                this.showIosHint = false;
                try { localStorage.setItem('iosHintDismissed', '1'); } catch {}
            }
        }));

    });
</script>
