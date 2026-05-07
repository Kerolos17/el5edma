<div
    x-data="{
        open: false,
        muted: localStorage.getItem('servant-notif-muted') === 'true',
        toggleMute() {
            this.muted = !this.muted;
            localStorage.setItem('servant-notif-muted', this.muted);
        },
        playSound(mode = 'soft') {
            if (this.muted) return;
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const patterns = {
                    soft:  [
                        { at: 0,    frequency: 720, duration: 0.12, gain: 0.14 },
                        { at: 0.18, frequency: 660, duration: 0.16, gain: 0.12 },
                    ],
                    alert: [
                        { at: 0,    frequency: 990, duration: 0.16, gain: 0.26 },
                        { at: 0.2,  frequency: 880, duration: 0.18, gain: 0.22 },
                        { at: 0.44, frequency: 990, duration: 0.22, gain: 0.24 },
                    ],
                };
                for (const note of (patterns[mode] ?? patterns.soft)) {
                    const osc  = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'square';
                    osc.frequency.setValueAtTime(note.frequency, ctx.currentTime + note.at);
                    gain.gain.setValueAtTime(0.0001, ctx.currentTime + note.at);
                    gain.gain.exponentialRampToValueAtTime(note.gain, ctx.currentTime + note.at + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + note.at + note.duration);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + note.at);
                    osc.stop(ctx.currentTime  + note.at + note.duration + 0.02);
                }
                setTimeout(() => ctx.close().catch(() => {}), 2000);
            } catch (e) {}
        }
    }"
    @new-notification-sound.window="playSound($event.detail || 'soft')"
    class="relative flex items-center gap-1"
    data-user-id="{{ Auth::id() }}"
    wire:poll.60000ms.visible="loadNotifications">

    {{-- Mute / Unmute --}}
    <button
        @click="toggleMute()"
        type="button"
        :title="muted ? 'تشغيل الصوت' : 'كتم الصوت'"
        class="w-8 h-8 rounded-full flex items-center justify-center transition"
        style="color: #006D77; opacity: 0.55;">
        <i x-show="!muted" class="ph ph-speaker-high text-lg"></i>
        <i x-show="muted"  class="ph ph-speaker-slash text-lg" style="display:none;"></i>
    </button>

    {{-- Bell Button --}}
    <button
        @click="open = !open"
        type="button"
        class="relative w-10 h-10 rounded-full flex items-center justify-center transition"
        style="color: #006D77;">
        @if ($unreadCount > 0)
            <i class="ph-fill ph-bell text-[22px]"></i>
            <span class="absolute top-1 left-1 w-4 h-4 rounded-full text-white text-[9px] font-bold flex items-center justify-center"
                  style="background:#E63946;">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @else
            <i class="ph ph-bell text-[22px]"></i>
        @endif
    </button>

    {{-- Backdrop --}}
    <div x-show="open" @click="open = false" class="fixed inset-0 z-[59]" style="display:none;" aria-hidden="true"></div>

    {{-- Dropdown --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="fixed inset-x-3 top-[4.25rem] z-60 rounded-2xl overflow-hidden
               sm:absolute sm:inset-x-auto sm:top-12 sm:end-0 sm:w-[22rem]"
        style="background:#fffbf7; box-shadow: 0 20px 60px rgba(0,109,119,0.15), 0 4px 16px rgba(0,0,0,0.08); border: 1px solid rgba(0,109,119,0.10); display:none;">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3.5 border-b" style="border-color: rgba(0,109,119,0.08);">
            <div class="flex items-center gap-2">
                <i class="ph-fill ph-bell text-base" style="color:#006D77;"></i>
                <span class="font-bold text-sm" style="color:#003942;">الإشعارات</span>
                @if ($unreadCount > 0)
                    <span class="px-2 py-0.5 text-xs font-bold rounded-full text-white" style="background:#006D77;">
                        {{ $unreadCount }}
                    </span>
                @endif
            </div>
            @if ($unreadCount > 0)
                <button wire:click="markAllRead" type="button"
                        class="text-xs font-semibold transition opacity-80 hover:opacity-100"
                        style="color:#006D77;">
                    تحديد كمقروء
                </button>
            @endif
        </div>

        {{-- List --}}
        <div class="max-h-[65dvh] sm:max-h-80 overflow-y-auto divide-y" style="divide-color: rgba(0,109,119,0.06);">
            @forelse ($notifications as $n)
                <div
                    wire:key="sn-{{ $n['id'] }}"
                    wire:click="markRead({{ $n['id'] }})"
                    class="flex gap-3 px-4 py-3.5 cursor-pointer transition-colors {{ $n['read'] ? '' : '' }}"
                    style="{{ !$n['read'] ? 'background: rgba(0,109,119,0.04); border-right: 3px solid #006D77;' : '' }}">

                    {{-- Icon bubble --}}
                    <div class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-sm"
                         style="{{ match($n['type']) {
                            'birthday'           => 'background:rgba(251,191,36,0.15); color:#D97706;',
                            'critical_case'      => 'background:rgba(230,57,70,0.12);  color:#E63946;',
                            'visit_reminder'     => 'background:rgba(0,109,119,0.12);  color:#006D77;',
                            'unvisited_alert'    => 'background:rgba(251,146,60,0.15); color:#EA580C;',
                            'new_beneficiary'    => 'background:rgba(34,197,94,0.12);  color:#16A34A;',
                            'servant_registered' => 'background:rgba(0,109,119,0.12);  color:#006D77;',
                            default              => 'background:rgba(0,109,119,0.08);  color:#006D77;',
                         } }}">
                        <i class="ph-fill {{ match($n['type']) {
                            'birthday'           => 'ph-cake',
                            'critical_case'      => 'ph-warning-circle',
                            'visit_reminder'     => 'ph-calendar-check',
                            'unvisited_alert'    => 'ph-clock-countdown',
                            'new_beneficiary'    => 'ph-user-plus',
                            'servant_registered' => 'ph-user-check',
                            default              => 'ph-bell',
                        } }} text-base"></i>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate" style="color:#003942;">{{ $n['title'] }}</p>
                        <p class="text-xs mt-0.5 line-clamp-2" style="color:#5f7b7e;">{{ $n['body'] }}</p>
                        <p class="text-[11px] mt-1" style="color:#9db4b6;">{{ $n['time'] }}</p>
                    </div>

                    @if (!$n['read'])
                        <div class="shrink-0 w-2 h-2 rounded-full mt-2 self-start flex-shrink-0" style="background:#006D77;"></div>
                    @endif
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-10 gap-2" style="color:#9db4b6;">
                    <i class="ph ph-bell-slash text-3xl"></i>
                    <p class="text-sm">لا توجد إشعارات</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
