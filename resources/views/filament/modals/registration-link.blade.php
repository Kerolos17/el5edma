{{-- Modal لعرض رابط التسجيل الذاتي للخدام --}}
{{-- Requirements: 7.1, 7.2, 7.3, 7.4, 7.5 --}}

<div class="space-y-4">
    {{-- حقل عرض الرابط --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            {{ __('service_groups.registration_url') }}
        </label>
        <div class="flex gap-2">
            <input type="text" value="{{ $url }}" readonly
                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-gray-50 dark:bg-gray-800 dark:text-gray-100 focus:outline-none"
                id="registration-url" onclick="this.select()" />
            <button type="button" onclick="copyToClipboard()"
                class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                    </path>
                </svg>
                <span id="copy-button-text">{{ __('service_groups.copy') }}</span>
            </button>
        </div>
    </div>

    {{-- معلومات إضافية --}}
    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                </path>
            </svg>
            <span>
                {{ __('service_groups.registered_servants_count') }}:
                <strong class="text-gray-900 dark:text-gray-100">{{ $registeredCount }}</strong>
            </span>
        </div>
    </div>

    {{-- JavaScript لنسخ الرابط --}}
    <script>
        function copyToClipboard() {
            const input = document.getElementById('registration-url');
            const button = document.getElementById('copy-button-text');

            // نسخ النص
            input.select();
            input.setSelectionRange(0, 99999); // للأجهزة المحمولة

            try {
                document.execCommand('copy');

                // تغيير نص الزر مؤقتاً
                const originalText = button.textContent;
                button.textContent = '{{ __('service_groups.copied') }}';

                // إعادة النص الأصلي بعد ثانيتين
                setTimeout(() => {
                    button.textContent = originalText;
                }, 2000);

                // إظهار إشعار Filament
                if (window.filament) {
                    window.filament.notifications.send({
                        message: '{{ __('service_groups.copied') }}',
                        status: 'success',
                    });
                }
            } catch (err) {
                console.error('فشل النسخ:', err);
            }

            // إلغاء التحديد
            window.getSelection().removeAllRanges();
        }
    </script>
</div>
