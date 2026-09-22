@props([
    'paginator',
])

@if($paginator->hasPages())
    <div class="flex justify-center items-center gap-3 pt-2">
        @if($paginator->onFirstPage())
            <span class="px-4 min-h-[44px] inline-flex items-center rounded-xl text-gray-300 text-sm font-semibold">السابق</span>
        @else
            <button wire:click="previousPage"
                    class="px-5 min-h-[44px] inline-flex items-center rounded-xl bg-teal-50 text-teal-700 text-sm font-semibold hover:bg-teal-100 transition-colors">
                السابق
            </button>
        @endif

        <span class="px-4 min-h-[44px] inline-flex items-center text-sm text-gray-500">
            {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </span>

        @if($paginator->hasMorePages())
            <button wire:click="nextPage"
                    class="px-5 min-h-[44px] inline-flex items-center rounded-xl bg-teal-50 text-teal-700 text-sm font-semibold hover:bg-teal-100 transition-colors">
                التالي
            </button>
        @else
            <span class="px-4 min-h-[44px] inline-flex items-center rounded-xl text-gray-300 text-sm font-semibold">التالي</span>
        @endif
    </div>
@endif
