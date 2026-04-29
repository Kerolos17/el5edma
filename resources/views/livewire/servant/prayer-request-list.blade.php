<div class="px-4 pt-6 pb-32 lg:pb-10 space-y-5">

    {{-- Page Title + FAB --}}
    <div class="reveal-card flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-teal-900">طلبات الصلاة</h1>
            <p class="text-sm text-gray-400 mt-0.5">{{ $prayerRequests->count() }} طلب</p>
        </div>
        <button wire:click="openForm"
                class="w-11 h-11 rounded-2xl gradient-deep flex items-center justify-center shadow-lg text-white"
                aria-label="إضافة طلب صلاة">
            <i class="ph-bold ph-plus text-lg" aria-hidden="true"></i>
        </button>
    </div>

    {{-- Filter Chips --}}
    <div class="flex gap-2 overflow-x-auto pb-1 reveal-card"
         style="animation-delay:0.06s; scrollbar-width:none;"
         role="group" aria-label="فلتر طلبات الصلاة">
        @foreach([['open','مفتوحة'], ['answered','مجابة'], ['closed','مغلقة'], ['all','الكل']] as [$val, $label])
            <button wire:click="$set('filter', '{{ $val }}')"
                    class="radio-chip flex-shrink-0 {{ $filter === $val ? 'selected' : '' }}"
                    aria-pressed="{{ $filter === $val ? 'true' : 'false' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Create Form --}}
    @if($showForm)
        <div class="s-card rounded-2xl p-5 space-y-4 reveal-card" role="region" aria-label="نموذج طلب صلاة جديد">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-teal-900">طلب صلاة جديد</h2>
                <button wire:click="closeForm"
                        class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center"
                        aria-label="إغلاق النموذج">
                    <i class="ph ph-x text-gray-500" aria-hidden="true"></i>
                </button>
            </div>

            <div>
                <label class="block text-sm font-semibold text-teal-900 mb-1.5">
                    المخدوم <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <select wire:model="beneficiaryId"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm bg-white
                               focus:ring-2 focus:ring-teal-400 focus:outline-none"
                        aria-required="true">
                    <option value="">-- اختر مخدوماً --</option>
                    @foreach($myBeneficiaries as $b)
                        <option value="{{ $b->id }}">{{ $b->full_name }}</option>
                    @endforeach
                </select>
                @error('beneficiaryId') <p class="text-xs text-red-500 mt-1" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-teal-900 mb-1.5">
                    الموضوع <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input wire:model.live.debounce.300ms="title"
                       type="text"
                       placeholder="موضوع الصلاة"
                       maxlength="255"
                       class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm
                              focus:ring-2 focus:ring-teal-400 focus:outline-none"
                       aria-required="true" />
                @error('title') <p class="text-xs text-red-500 mt-1" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-teal-900 mb-1.5">التفاصيل</label>
                <textarea wire:model="body"
                          rows="3"
                          placeholder="تفاصيل إضافية (اختياري)"
                          maxlength="2000"
                          class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm
                                 focus:ring-2 focus:ring-teal-400 focus:outline-none resize-none"></textarea>
            </div>

            <button wire:click="save"
                    wire:loading.attr="disabled"
                    class="w-full py-3 rounded-2xl gradient-deep text-white font-bold text-sm
                           shadow-lg disabled:opacity-60 transition-opacity">
                <span wire:loading.remove wire:target="save">حفظ طلب الصلاة</span>
                <span wire:loading wire:target="save">جاري الحفظ...</span>
            </button>
        </div>
    @endif

    {{-- Skeleton --}}
    <div wire:loading.delay class="space-y-3" aria-hidden="true">
        @for ($i = 0; $i < 4; $i++)
            <div class="skeleton-shimmer rounded-2xl" style="height:80px;"></div>
        @endfor
    </div>

    {{-- Prayer Request Cards --}}
    <div wire:loading.remove class="space-y-3">
        @forelse($prayerRequests as $pr)
            <div class="s-card card-lift rounded-2xl px-4 py-3" role="article">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-teal-900 text-sm truncate">{{ $pr->title }}</p>
                        <p class="text-xs text-teal-500 mt-0.5">{{ $pr->beneficiary?->full_name ?? '' }}</p>
                        @if($pr->body)
                            <p class="text-xs text-gray-500 mt-1.5 line-clamp-2">{{ $pr->body }}</p>
                        @endif
                    </div>
                    <span @class([
                        'badge-pill text-xs px-2 py-0.5 flex-shrink-0',
                        'badge-info'    => $pr->status === 'open',
                        'badge-success' => $pr->status === 'answered',
                        'bg-gray-100 text-gray-500' => $pr->status === 'closed',
                    ])>
                        @match($pr->status)
                            'open'     => 'مفتوح',
                            'answered' => 'مجاب',
                            'closed'   => 'مغلق',
                            default    => $pr->status
                        @endmatch
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    <i class="ph ph-clock text-xs" aria-hidden="true"></i>
                    {{ $pr->created_at->locale('ar')->diffForHumans() }}
                </p>
            </div>
        @empty
            <x-ui.empty-state
                icon="ph-hands-praying"
                message="{{ $filter === 'open' ? 'لا توجد طلبات صلاة مفتوحة' : 'لا توجد طلبات' }}"
            />
        @endforelse
    </div>

</div>
