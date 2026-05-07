{{--
    Offline Banner — يظهر أعلى الصفحة عند فقدان الاتصال.
    Alpine.js يستمع لـ window online/offline events.
--}}
<div
    x-data="{
        offline: !navigator.onLine,
        init() {
            window.addEventListener('online',  () => this.offline = false);
            window.addEventListener('offline', () => this.offline = true);
        }
    }"
    x-show="offline"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-full"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-full"
    class="fixed top-0 left-0 right-0 z-[200] flex items-center justify-center gap-2 px-4 text-sm font-semibold text-white"
    style="background: linear-gradient(135deg, #E63946, #C2323E); padding-top: max(0.625rem, env(safe-area-inset-top)); padding-bottom: 0.625rem;"
    role="alert"
    aria-live="assertive"
    aria-atomic="true">
    <i class="ph-fill ph-wifi-slash text-base" aria-hidden="true"></i>
    <span>أنت غير متصل بالإنترنت</span>
    <span class="text-white/70 text-xs mr-1">— الزيارات ستُحفظ محلياً</span>
</div>
