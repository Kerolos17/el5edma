@php
    $visitTypes = [
        'home_visit'     => ['label' => 'زيارة منزلية',   'icon' => 'ph-house-simple',   'color' => '#006D77'],
        'phone_call'     => ['label' => 'مكالمة هاتفية',  'icon' => 'ph-phone',           'color' => '#4D9BA3'],
        'church_meeting' => ['label' => 'اجتماع كنيسة',   'icon' => 'ph-church',          'color' => '#F4A261'],
    ];
    $statuses = [
        'great'        => ['label' => 'ممتاز',          'color' => '#06A77D', 'bg' => 'rgba(6,167,125,0.12)'],
        'good'         => ['label' => 'جيد',            'color' => '#4D9BA3', 'bg' => 'rgba(77,155,163,0.12)'],
        'needs_follow' => ['label' => 'يحتاج متابعة',   'color' => '#F77F00', 'bg' => 'rgba(247,127,0,0.12)'],
        'critical'     => ['label' => 'حرج',            'color' => '#E63946', 'bg' => 'rgba(230,57,70,0.12)'],
    ];
@endphp

{{-- Single Livewire root — Alpine entangles $open for CSS-transition-safe animation --}}
<div x-data="{ open: $wire.entangle('open') }">

    {{-- Overlay --}}
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         wire:click="close"
         class="fixed inset-0 z-[150]"
         style="background: rgba(0,61,66,0.45); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);">
    </div>

    {{-- Bottom Sheet — Alpine drives transform so CSS transition fires on every open/close --}}
    <div class="fixed bottom-0 left-0 right-0 z-[160]"
         :style="{ transform: open ? 'translateY(0)' : 'translateY(100%)' }"
         style="transform: translateY(100%); transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)">

        <div class="rounded-t-[28px] overflow-hidden"
             style="background: #FFFBF7; max-height: 92vh; overflow-y: auto;">

            {{-- Drag Handle --}}
            <div class="flex justify-center pt-3 pb-1">
                <div class="w-10 h-1 rounded-full bg-gray-200"></div>
            </div>

            {{-- Step Indicator --}}
            <div class="px-5 pt-2 pb-4">
                <div class="flex items-center gap-1 mb-3">
                    @for($i = 1; $i <= 4; $i++)
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 transition-all duration-300"
                             style="{{ $step >= $i
                                ? 'background: linear-gradient(135deg, #006D77, #003942); color: white;'
                                : 'background: #F5F3F0; color: #9CA3AF;' }}">
                            {{ $i }}
                        </div>
                        @if($i < 4)
                            <div class="step-connector {{ $step > $i ? 'completed' : '' }}"></div>
                        @endif
                    @endfor
                </div>

                {{-- Step Title --}}
                <h3 class="font-bold text-teal-900 text-lg" style="font-family: var(--font-display);">
                    @if($step === 1) اختيار المخدوم
                    @elseif($step === 2) نوع الزيارة
                    @elseif($step === 3) تفاصيل الزيارة
                    @else ملخص وتأكيد
                    @endif
                </h3>
            </div>

            {{-- ══════════════════ STEP 1: Select Beneficiary ══════════════════ --}}
            @if($step === 1)
                <div class="px-5 pb-6 space-y-4">

                    @if($selectedBeneficiary)
                        {{-- Selected beneficiary card --}}
                        <div class="s-card rounded-2xl px-4 py-3 flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl overflow-hidden flex-shrink-0">
                                @if($selectedBeneficiary->photo_url)
                                    <img src="{{ $selectedBeneficiary->photo_url }}" class="w-full h-full object-cover" alt="">
                                @else
                                    <div class="w-full h-full flex items-center justify-center font-bold text-teal-700"
                                         style="background: linear-gradient(135deg, #C7E5E8, #4D9BA3);">
                                        {{ mb_substr($selectedBeneficiary->full_name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-teal-900 text-sm truncate">{{ $selectedBeneficiary->full_name }}</p>
                                <p class="text-xs text-gray-400">{{ $selectedBeneficiary->code }}</p>
                            </div>
                            <button wire:click="clearBeneficiary"
                                    class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 hover:bg-gray-100 active:bg-gray-200 transition-colors"
                                    aria-label="إلغاء اختيار المخدوم">
                                <i class="ph ph-x text-gray-500 text-sm" aria-hidden="true"></i>
                            </button>
                        </div>
                    @else
                        {{-- Search --}}
                        <div class="relative">
                            <i class="ph ph-magnifying-glass absolute top-1/2 -translate-y-1/2 right-4 text-gray-400 text-lg pointer-events-none"></i>
                            <input wire:model.live.debounce.250ms="beneficiarySearch"
                                   type="search"
                                   placeholder="ابحث بالاسم أو الكود..."
                                   class="search-input"
                                   style="padding-right: 44px;">
                        </div>

                        {{-- Beneficiary List --}}
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @forelse($beneficiaries as $b)
                                <button wire:click="selectBeneficiary({{ $b->id }})"
                                        class="w-full text-right s-card rounded-2xl px-4 py-3 flex items-center gap-3 hover:shadow-md transition-all duration-200 active:scale-[0.98]">
                                    <div class="w-10 h-10 rounded-xl overflow-hidden flex-shrink-0">
                                        @if($b->photo_url)
                                            <img src="{{ $b->photo_url }}" class="w-full h-full object-cover" alt="">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center font-bold text-teal-700 text-sm"
                                                 style="background: linear-gradient(135deg, #C7E5E8, #4D9BA3);">
                                                {{ mb_substr($b->full_name, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-teal-900 text-sm truncate">{{ $b->full_name }}</p>
                                        <p class="text-xs text-gray-400">{{ $b->code }}</p>
                                    </div>
                                </button>
                            @empty
                                <div class="text-center py-8">
                                    <i class="ph ph-users text-4xl text-gray-300 block mb-2"></i>
                                    <p class="text-sm text-gray-400">
                                        {{ $beneficiarySearch ? 'لا توجد نتائج' : 'ابحث عن مخدوم' }}
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    @endif

                    @error('selectedBeneficiaryId')
                        <p class="text-red-500 text-sm flex items-center gap-1">
                            <i class="ph ph-warning-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            @endif

            {{-- ══════════════════ STEP 2: Visit Type ══════════════════ --}}
            @if($step === 2)
                <div class="px-5 pb-6 space-y-3">
                    @foreach($visitTypes as $key => $type)
                        <button wire:click="$set('visitType', '{{ $key }}')"
                                class="w-full text-right rounded-2xl px-5 py-4 flex items-center gap-4 transition-all duration-200 border-2
                                       {{ $visitType === $key ? 'border-teal-500 bg-teal-50' : 'border-gray-100 bg-white hover:border-teal-200' }}">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                                 style="background: {{ $visitType === $key ? $type['color'] : '#F5F3F0' }}">
                                <i class="ph-bold {{ $type['icon'] }} text-xl"
                                   style="color: {{ $visitType === $key ? 'white' : '#9CA3AF' }}"></i>
                            </div>
                            <span class="font-bold text-base {{ $visitType === $key ? 'text-teal-900' : 'text-gray-600' }}">
                                {{ $type['label'] }}
                            </span>
                            @if($visitType === $key)
                                <span class="mr-auto w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0"
                                      style="background: #006D77">
                                    <i class="ph-bold ph-check text-white text-xs"></i>
                                </span>
                            @endif
                        </button>
                    @endforeach

                    @error('visitType')
                        <p class="text-red-500 text-sm flex items-center gap-1">
                            <i class="ph ph-warning-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            @endif

            {{-- ══════════════════ STEP 3: Details ══════════════════ --}}
            @if($step === 3)
                <div class="px-5 pb-6 space-y-5">

                    {{-- Beneficiary Status --}}
                    <div>
                        <p class="text-sm font-bold text-teal-900 mb-3">الحالة العامة للمخدوم</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($statuses as $key => $status)
                                <button wire:click="$set('beneficiaryStatus', '{{ $key }}')"
                                        class="py-3 rounded-2xl text-center font-bold text-sm transition-all duration-200 border-2"
                                        style="{{ $beneficiaryStatus === $key
                                            ? "border-color: {$status['color']}; background: {$status['bg']}; color: {$status['color']};"
                                            : 'border-color: #E8E4DF; background: white; color: #6B7280;' }}">
                                    {{ $status['label'] }}
                                </button>
                            @endforeach
                        </div>
                        @error('beneficiaryStatus')
                            <p class="text-red-500 text-sm mt-1 flex items-center gap-1">
                                <i class="ph ph-warning-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Duration --}}
                    <div>
                        <label class="text-sm font-bold text-teal-900 mb-2 block">مدة الزيارة (دقيقة)</label>
                        <div class="flex gap-2 flex-wrap">
                            @foreach([15, 30, 45, 60, 90] as $min)
                                <button wire:click="$set('durationMinutes', {{ $min }})"
                                        class="px-4 min-h-[44px] rounded-xl text-sm font-bold transition-all duration-200 border-2
                                               {{ $durationMinutes == $min ? 'border-teal-500 bg-teal-50 text-teal-700' : 'border-gray-100 bg-white text-gray-500 hover:border-teal-200' }}">
                                    {{ $min }}د
                                </button>
                            @endforeach
                            <input wire:model.lazy="durationMinutes"
                                   type="number" min="1" max="480" placeholder="أخرى..."
                                   class="w-20 min-h-[44px] px-3 rounded-xl border-2 border-gray-100 text-sm text-center font-bold text-gray-600 outline-none focus:border-teal-400 transition-colors">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="text-sm font-bold text-teal-900 mb-2 block">ملاحظات وتقرير الزيارة</label>
                        <textarea wire:model.lazy="feedback"
                                  class="form-textarea"
                                  rows="4"
                                  placeholder="اكتب ملاحظاتك عن الزيارة، الحالة الروحية، الاحتياجات..."></textarea>
                    </div>

                    {{-- Critical + Escalation Flags --}}
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 s-card rounded-2xl px-4 py-3 cursor-pointer"
                               style="{{ $isCritical ? 'border: 2px solid #E63946; background: rgba(230,57,70,0.04)' : '' }}">
                            <input wire:model="isCritical" type="checkbox"
                                   class="w-5 h-5 rounded accent-red-500 cursor-pointer flex-shrink-0">
                            <div>
                                <p class="font-bold text-sm {{ $isCritical ? 'text-red-600' : 'text-gray-700' }}">حالة حرجة</p>
                                <p class="text-xs text-gray-400">يحتاج تدخل عاجل</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 s-card rounded-2xl px-4 py-3 cursor-pointer"
                               style="{{ $needsFamilyLeader ? 'border: 2px solid #F4A261; background: rgba(244,162,97,0.04)' : '' }}">
                            <input wire:model="needsFamilyLeader" type="checkbox"
                                   class="w-5 h-5 rounded accent-orange-400 cursor-pointer flex-shrink-0">
                            <div>
                                <p class="font-bold text-sm {{ $needsFamilyLeader ? 'text-orange-600' : 'text-gray-700' }}">يحتاج أمين الأسرة</p>
                                <p class="text-xs text-gray-400">إحالة لأمين الأسرة</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 s-card rounded-2xl px-4 py-3 cursor-pointer"
                               style="{{ $needsServiceLeader ? 'border: 2px solid #4D9BA3; background: rgba(77,155,163,0.04)' : '' }}">
                            <input wire:model="needsServiceLeader" type="checkbox"
                                   class="w-5 h-5 rounded accent-teal-500 cursor-pointer flex-shrink-0">
                            <div>
                                <p class="font-bold text-sm {{ $needsServiceLeader ? 'text-teal-700' : 'text-gray-700' }}">يحتاج رئيس الخدمة</p>
                                <p class="text-xs text-gray-400">إحالة لرئيس الخدمة</p>
                            </div>
                        </label>
                    </div>
                </div>
            @endif

            {{-- ══════════════════ STEP 4: Summary & Confirm ══════════════════ --}}
            @if($step === 4)
                <div class="px-5 pb-6 space-y-4">

                    {{-- Beneficiary --}}
                    @if($selectedBeneficiary)
                        <div class="s-card rounded-2xl px-4 py-3 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl overflow-hidden flex-shrink-0">
                                @if($selectedBeneficiary->photo_url)
                                    <img src="{{ $selectedBeneficiary->photo_url }}" class="w-full h-full object-cover" alt="">
                                @else
                                    <div class="w-full h-full flex items-center justify-center font-bold text-teal-700"
                                         style="background: linear-gradient(135deg, #C7E5E8, #4D9BA3);">
                                        {{ mb_substr($selectedBeneficiary->full_name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">المخدوم</p>
                                <p class="font-bold text-teal-900 text-sm">{{ $selectedBeneficiary->full_name }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Summary Grid --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="s-card rounded-2xl p-3 text-center">
                            <i class="ph-bold {{ $visitTypes[$visitType]['icon'] ?? 'ph-calendar' }} text-xl mb-1 block"
                               style="color: #006D77"></i>
                            <p class="text-xs text-gray-400">نوع الزيارة</p>
                            <p class="font-bold text-sm text-teal-900">{{ $visitTypes[$visitType]['label'] ?? '' }}</p>
                        </div>

                        <div class="s-card rounded-2xl p-3 text-center">
                            <div class="w-5 h-5 rounded-full mx-auto mb-1"
                                 style="background: {{ $statuses[$beneficiaryStatus]['color'] ?? '#ccc' }}"></div>
                            <p class="text-xs text-gray-400">الحالة</p>
                            <p class="font-bold text-sm text-teal-900">{{ $statuses[$beneficiaryStatus]['label'] ?? '' }}</p>
                        </div>

                        @if($durationMinutes)
                            <div class="s-card rounded-2xl p-3 text-center">
                                <i class="ph ph-clock text-xl mb-1 block text-teal-500"></i>
                                <p class="text-xs text-gray-400">المدة</p>
                                <p class="font-bold text-sm text-teal-900">{{ $durationMinutes }} دقيقة</p>
                            </div>
                        @endif

                        <div class="s-card rounded-2xl p-3 text-center">
                            <i class="ph ph-calendar-check text-xl mb-1 block text-teal-500"></i>
                            <p class="text-xs text-gray-400">التاريخ</p>
                            <p class="font-bold text-sm text-teal-900">{{ now()->locale('ar')->isoFormat('D MMM') }}</p>
                        </div>
                    </div>

                    @if($feedback)
                        <div class="s-card rounded-2xl px-4 py-3">
                            <p class="text-xs text-gray-400 mb-1">الملاحظات</p>
                            <p class="text-sm text-gray-700 leading-relaxed">{{ $feedback }}</p>
                        </div>
                    @endif

                    @if($isCritical || $needsFamilyLeader || $needsServiceLeader)
                        <div class="flex flex-wrap gap-2">
                            @if($isCritical)
                                <span class="badge-pill badge-critical text-xs">
                                    <i class="ph-fill ph-warning"></i> حالة حرجة
                                </span>
                            @endif
                            @if($needsFamilyLeader)
                                <span class="badge-pill badge-warning text-xs">
                                    <i class="ph ph-user-gear"></i> يحتاج أمين الأسرة
                                </span>
                            @endif
                            @if($needsServiceLeader)
                                <span class="badge-pill badge-info text-xs">
                                    <i class="ph ph-user-crown"></i> يحتاج رئيس الخدمة
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            {{-- Navigation Buttons --}}
            <div class="px-5 pb-8 flex gap-3"
                 style="padding-bottom: max(2rem, env(safe-area-inset-bottom));">

                @if($step > 1)
                    <button wire:click="prevStep"
                            class="flex-1 py-3.5 rounded-2xl font-bold text-sm border-2 border-gray-200 text-gray-600 hover:border-teal-300 transition-all duration-200">
                        رجوع
                    </button>
                @else
                    <button wire:click="close"
                            class="flex-1 py-3.5 rounded-2xl font-bold text-sm border-2 border-gray-200 text-gray-600 hover:border-teal-300 transition-all duration-200">
                        إلغاء
                    </button>
                @endif

                @if($step < 4)
                    <button wire:click="nextStep"
                            class="flex-[2] py-3.5 rounded-2xl font-bold text-sm text-white btn-ripple transition-all duration-200"
                            style="background: linear-gradient(135deg, #006D77 0%, #003942 100%);">
                        التالي
                    </button>
                @else
                    {{-- زر الحفظ: عند الأوفلاين يُخزّن في IndexedDB، وعند الأونلاين يُرسل عبر Livewire --}}
                    <button
                        x-data
                        @click.prevent="
                            if (!navigator.onLine) {
                                const { offlineQueue } = await import('/resources/js/servant.js').catch(() => window.offlineQueue ?? null) ?? {};
                                const queue = window.__servantOfflineQueue;
                                if (queue) {
                                    await queue.enqueue({
                                        beneficiary_id:     $wire.selectedBeneficiaryId,
                                        visitType:          $wire.visitType,
                                        beneficiaryStatus:  $wire.beneficiaryStatus,
                                        durationMinutes:    $wire.durationMinutes,
                                        feedback:           $wire.feedback,
                                        isCritical:         $wire.isCritical,
                                        needsFamilyLeader:  $wire.needsFamilyLeader,
                                        needsServiceLeader: $wire.needsServiceLeader,
                                        queuedAt:           Date.now(),
                                    });
                                    $wire.close();
                                    $dispatch('toast', { message: 'سيتم رفع الزيارة عند اتصالك بالإنترنت', type: 'warning' });
                                }
                            } else {
                                $wire.submit();
                            }
                        "
                        wire:loading.attr="disabled"
                        class="flex-[2] py-3.5 rounded-2xl font-bold text-sm text-white btn-ripple transition-all duration-200 disabled:opacity-60"
                        style="background: linear-gradient(135deg, #06A77D 0%, #048C68 100%);">
                        <span wire:loading.remove wire:target="submit">تأكيد وحفظ الزيارة</span>
                        <span wire:loading wire:target="submit">جاري الحفظ...</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
