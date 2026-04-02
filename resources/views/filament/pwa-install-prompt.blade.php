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

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pwaInstallPrompt', () => ({
            showPrompt: false,
            deferredPrompt: null,

            init() {
                // Check if user previously dismissed
                try {
                    if (localStorage.getItem('pwaPromptDismissed')) {
                        return;
                    }
                } catch (e) {
                    // silent fail — نكمل وكأن المستخدم لم يرفض من قبل
                }

                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.deferredPrompt = e;

                    // Add a slight delay before showing the prompt so it's not too aggressive
                    setTimeout(() => {
                        this.showPrompt = true;
                    }, 3000);
                });

                window.addEventListener('appinstalled', (evt) => {
                    this.showPrompt = false;
                    this.deferredPrompt = null;
                    console.log('App was successfully installed');
                });
            },

            async install() {
                this.showPrompt = false;
                if (!this.deferredPrompt) {
                    return;
                }

                this.deferredPrompt.prompt();
                const {
                    outcome
                } = await this.deferredPrompt.userChoice;

                console.log(`User interaction with install prompt: ${outcome}`);
                this.deferredPrompt = null;
            },

            dismiss() {
                this.showPrompt = false;
                try {
                    localStorage.setItem('pwaPromptDismissed', 'true');
                } catch (e) {
                    // silent fail — الـ prompt سيظهر مرة أخرى في الجلسة التالية
                }
            }
        }))
    })
</script>
