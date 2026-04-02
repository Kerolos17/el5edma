<x-filament-panels::page>
    <div class="space-y-6">

        {{-- فلاتر --}}
        <div
            class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-[var(--bg-surface)] rounded-xl shadow-sm border border-[var(--color-border)]">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('reports.date_from') }}
                </label>
                <input type="date" wire:model="dateFrom"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('reports.date_to') }}
                </label>
                <input type="date" wire:model="dateTo"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100" />
            </div>
        </div>

        {{-- بطاقات التقارير --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

            {{-- تقرير المخدومين --}}
            <div class="p-5 bg-[var(--bg-surface)] rounded-xl shadow-sm border border-[var(--color-border)]">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center text-xl">👥</div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('reports.r1_title') }}</h3>
                </div>
                <p class="text-xs text-gray-500 mb-4">
                    {{ __('reports.r1_description') }}
                </p>
                <div class="flex flex-col sm:flex-row gap-2">
                    <button wire:click="exportBeneficiariesExcel"
                        class="flex-1 px-4 py-3 sm:px-3 sm:py-2 bg-green-600 text-white text-sm sm:text-xs font-medium rounded-lg hover:bg-green-700 transition flex justify-center items-center gap-2">
                        <span>📊</span> <span>Excel</span>
                    </button>
                    <a href="{{ $this->getBeneficiariesPdfUrl() }}" target="_blank"
                        class="flex-1 px-4 py-3 sm:px-3 sm:py-2 bg-red-600 text-white text-sm sm:text-xs font-medium rounded-lg hover:bg-red-700 transition flex justify-center items-center gap-2">
                        <span>📄</span> <span>PDF</span>
                    </a>
                </div>
            </div>

            {{-- تقرير الزيارات --}}
            <div class="p-5 bg-[var(--bg-surface)] rounded-xl shadow-sm border border-[var(--color-border)]">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-xl">📋</div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('reports.r2_title') }}</h3>
                </div>
                <p class="text-xs text-gray-500 mb-4">
                    {{ __('reports.r2_description') }}
                </p>
                <div class="flex flex-col sm:flex-row gap-2">
                    <button wire:click="exportVisitsExcel"
                        class="flex-1 px-4 py-3 sm:px-3 sm:py-2 bg-green-600 text-white text-sm sm:text-xs font-medium rounded-lg hover:bg-green-700 transition flex justify-center items-center gap-2">
                        <span>📊</span> <span>Excel</span>
                    </button>
                    <a href="{{ $this->getVisitsPdfUrl() }}" target="_blank"
                        class="flex-1 px-4 py-3 sm:px-3 sm:py-2 bg-red-600 text-white text-sm sm:text-xs font-medium rounded-lg hover:bg-red-700 transition flex justify-center items-center gap-2">
                        <span>📄</span> <span>PDF</span>
                    </a>
                </div>
            </div>

            {{-- تقرير غير المزارين --}}
            <div class="p-5 bg-[var(--bg-surface)] rounded-xl shadow-sm border border-[var(--color-border)]">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center text-xl">⚠️</div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('reports.r3_title') }}</h3>
                </div>
                <p class="text-xs text-gray-500 mb-4">
                    {{ __('reports.r3_description') }}
                </p>
                <div class="flex flex-col sm:flex-row gap-2">
                    <a href="{{ $this->getUnvisitedPdfUrl() }}" target="_blank"
                        class="flex-1 px-4 py-3 sm:px-3 sm:py-2 bg-red-600 text-white text-sm sm:text-xs font-medium rounded-lg hover:bg-red-700 transition flex justify-center items-center gap-2">
                        <span>📄</span> <span>PDF</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-filament-panels::page>
