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
                    soft: [
                        { at: 0, frequency: 720, duration: 0.12, gain: 0.14 },
                        { at: 0.18, frequency: 660, duration: 0.16, gain: 0.12 },
                    ],
                    alert: [
                        { at: 0, frequency: 990, duration: 0.16, gain: 0.26 },
                        { at: 0.2, frequency: 880, duration: 0.18, gain: 0.22 },
                        { at: 0.44, frequency: 990, duration: 0.22, gain: 0.24 },
                    ],
                };
                for (const note of (patterns[mode] ?? patterns.soft)) {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'square';
                    osc.frequency.setValueAtTime(note.frequency, ctx.currentTime + note.at);
                    gain.gain.setValueAtTime(0.0001, ctx.currentTime + note.at);
                    gain.gain.exponentialRampToValueAtTime(note.gain, ctx.currentTime + note.at + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + note.at + note.duration);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + note.at);
                    osc.stop(ctx.currentTime + note.at + note.duration + 0.02);
                }
                setTimeout(() => ctx.close().catch(() => {}), 2000);
            } catch (e) {}
        }
    }"
    @new-notification-sound.window="playSound($event.detail || 'soft')"
    class="app-notification-root"
    data-user-id="<?php echo e(Auth::id()); ?>"
    wire:poll.60000ms.visible="loadNotifications">

    <button
        @click="toggleMute()"
        type="button"
        :title="muted ? '<?php echo e(__('notifications.sound_unmute')); ?>' : '<?php echo e(__('notifications.sound_mute')); ?>'"
        class="app-notification-mute">
        <i x-show="!muted" class="ph ph-speaker-high" aria-hidden="true"></i>
        <i x-show="muted" class="ph ph-speaker-slash" style="display:none;" aria-hidden="true"></i>
    </button>

    <button
        @click="open = !open"
        type="button"
        class="app-notification-bell"
        title="<?php echo e(__('notifications.title')); ?>">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($unreadCount > 0): ?>
            <i class="ph-fill ph-bell" aria-hidden="true"></i>
            <span><?php echo e($unreadCount > 9 ? '9+' : $unreadCount); ?></span>
        <?php else: ?>
            <i class="ph ph-bell" aria-hidden="true"></i>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </button>

    <div x-show="open" @click="open = false" class="app-notification-backdrop" style="display:none;" aria-hidden="true"></div>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="app-notification-panel"
        style="display:none;">

        <div class="app-notification-header">
            <div>
                <i class="ph-fill ph-bell" aria-hidden="true"></i>
                <span><?php echo e(__('notifications.title')); ?></span>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($unreadCount > 0): ?>
                    <strong><?php echo e($unreadCount); ?></strong>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($unreadCount > 0): ?>
                <button wire:click="markAllRead" type="button">
                    <?php echo e(__('notifications.mark_all_read')); ?>

                </button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <a href="<?php echo e(route('app.notifications')); ?>" wire:navigate class="app-notification-view-all">
                <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
            </a>
        </div>

        <div class="app-notification-list">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <button
                    type="button"
                    wire:key="sn-<?php echo e($n['id']); ?>"
                    wire:click="markRead(<?php echo e($n['id']); ?>)"
                    class="app-notification-item <?php echo e($n['read'] ? '' : 'is-unread'); ?>">
                    <span class="app-notification-type">
                        <i class="ph-fill <?php echo e(match($n['type']) {
                            'birthday' => 'ph-cake',
                            'critical_case' => 'ph-warning-circle',
                            'visit_reminder' => 'ph-calendar-check',
                            'unvisited_alert' => 'ph-clock-countdown',
                            'new_beneficiary' => 'ph-user-plus',
                            'servant_registered' => 'ph-user-check',
                            default => 'ph-bell',
                        }); ?>" aria-hidden="true"></i>
                    </span>

                    <span class="app-notification-content">
                        <strong><?php echo e($n['title']); ?></strong>
                        <small><?php echo e($n['body']); ?></small>
                        <time><?php echo e($n['time']); ?></time>
                    </span>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $n['read']): ?>
                        <span class="app-notification-dot" aria-hidden="true"></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="app-notification-empty">
                    <i class="ph ph-bell-slash" aria-hidden="true"></i>
                    <p><?php echo e(__('notifications.no_notifications')); ?></p>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH E:\ministry-system\resources\views/livewire/servant/notifications-bell.blade.php ENDPATH**/ ?>