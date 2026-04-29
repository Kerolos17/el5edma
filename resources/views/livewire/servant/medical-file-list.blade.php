<div class="px-4 pt-6 pb-32 lg:pb-10 space-y-5">

    {{-- Page Title --}}
    <div class="reveal-card">
        <h1 class="text-xl font-bold text-teal-900">الملفات الطبية</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $medicalFiles->count() }} ملف</p>
    </div>

    {{-- Filter Chips --}}
    <div class="flex gap-2 overflow-x-auto pb-1 reveal-card"
         style="animation-delay: 0.06s; scrollbar-width: none;"
         role="group" aria-label="فلتر الملفات الطبية">
        @foreach([['all','الكل'], ['report','تقارير'], ['image','صور'], ['document','مستندات']] as [$val, $label])
            <button wire:click="$set('filter', '{{ $val }}')"
                    class="radio-chip flex-shrink-0 {{ $filter === $val ? 'selected' : '' }}"
                    aria-pressed="{{ $filter === $val ? 'true' : 'false' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Skeleton --}}
    <div wire:loading.delay class="space-y-3" aria-hidden="true">
        @for ($i = 0; $i < 4; $i++)
            <div class="skeleton-shimmer rounded-2xl" style="height:72px;"></div>
        @endfor
    </div>

    {{-- File Cards --}}
    <div wire:loading.remove class="space-y-3">
        @forelse($medicalFiles as $file)
            <div class="s-card card-lift rounded-2xl px-4 py-3 flex items-center gap-3" role="article">

                {{-- Icon --}}
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: rgba(0,109,119,0.1);" aria-hidden="true">
                    @switch($file->file_type)
                        @case('report')
                            <i class="ph-fill ph-file-text text-teal-600 text-xl"></i>
                            @break
                        @case('image')
                            <i class="ph-fill ph-image text-teal-600 text-xl"></i>
                            @break
                        @default
                            <i class="ph-fill ph-file text-teal-600 text-xl"></i>
                    @endswitch
                </div>

                <div class="flex-1 min-w-0">
                    <p class="font-bold text-teal-900 text-sm truncate">{{ $file->title }}</p>
                    <p class="text-xs text-teal-500 mt-0.5">{{ $file->beneficiary?->full_name ?? '' }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        @switch($file->file_type)
                            @case('report') تقرير طبي @break
                            @case('image') صورة طبية @break
                            @case('document') مستند @break
                            @default {{ $file->file_type }}
                        @endswitch
                    </p>
                </div>

                {{-- Download link --}}
                <a href="{{ route('medical-files.download', $file->id) }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="flex-shrink-0 w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center
                          hover:bg-teal-100 transition-colors"
                   aria-label="تحميل {{ $file->title }}">
                    <i class="ph ph-download-simple text-teal-600 text-base" aria-hidden="true"></i>
                </a>
            </div>
        @empty
            <div class="s-card rounded-2xl px-4 py-10 text-center">
                <i class="ph ph-folder-open text-4xl text-teal-200 block mb-2"></i>
                <p class="text-sm text-gray-400">
                    {{ $filter !== 'all' ? 'لا توجد ملفات من هذا النوع' : 'لا توجد ملفات طبية' }}
                </p>
            </div>
        @endforelse
    </div>

</div>
