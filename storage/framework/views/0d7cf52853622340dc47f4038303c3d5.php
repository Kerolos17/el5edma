<section class="app-page-stack">
     <?php $__env->slot('title', null, []); ?> <?php echo e($title); ?> <?php $__env->endSlot(); ?>

    <div class="app-hero-panel">
        <div>
            <p class="app-section-label"><?php echo e($roleLabel); ?></p>
            <h2><?php echo e(__('web_app.dashboard.greeting', ['name' => auth()->user()->name])); ?></h2>
        </div>
        <div class="app-hero-actions">
            <a href="<?php echo e(route('app.beneficiaries')); ?>" wire:navigate class="app-primary-button">
                <i class="ph ph-users-three" aria-hidden="true"></i>
                <?php echo e(__('web_app.actions.beneficiaries')); ?>

            </a>
            <a href="<?php echo e(route('app.visits')); ?>" wire:navigate class="app-secondary-button">
                <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                <?php echo e(__('web_app.actions.visits')); ?>

            </a>
        </div>
    </div>

    <div class="app-stat-grid">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $stats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <article class="app-stat-card tone-<?php echo e($stat['tone']); ?>">
                <div class="app-stat-icon">
                    <i class="ph <?php echo e($stat['icon']); ?>" aria-hidden="true"></i>
                </div>
                <div>
                    <p><?php echo e($stat['label']); ?></p>
                    <strong><?php echo e(number_format($stat['value'])); ?></strong>
                </div>
            </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <div class="app-dashboard-grid">
        
        <div class="space-y-6">
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label"><?php echo e(__('web_app.dashboard.recent_activity')); ?></p>
                        <h3><?php echo e(__('web_app.dashboard.recent_visits')); ?></h3>
                    </div>
                    <a href="<?php echo e(route('app.visits')); ?>" wire:navigate><?php echo e(__('web_app.actions.view_all')); ?></a>
                </div>

                <div class="app-activity-list">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentVisits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $visit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <article class="app-activity-row">
                            <div>
                                <strong><?php echo e($visit->beneficiary?->full_name ?? __('web_app.dashboard.unknown_name')); ?></strong>
                                <span><?php echo e($visit->createdBy?->name ?? __('web_app.dashboard.unknown_user')); ?></span>
                            </div>
                            <time><?php echo e($visit->visit_date?->format('Y-m-d')); ?></time>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="app-empty-state">
                            <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                            <p><?php echo e(__('web_app.dashboard.empty_visits')); ?></p>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </section>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($criticalCases->isNotEmpty()): ?>
                <section class="app-panel">
                    <div class="app-panel-header">
                        <div>
                            <p class="app-section-label"><?php echo e(__('web_app.dashboard.stats.critical_cases')); ?></p>
                            <h3><?php echo e(__('web_app.dashboard.critical_cases')); ?></h3>
                        </div>
                        <a href="<?php echo e(route('app.beneficiaries', ['filter' => 'critical'])); ?>" wire:navigate><?php echo e(__('web_app.actions.view_all')); ?></a>
                    </div>

                    <div class="app-table-wrap">
                        <table class="app-table">
                            <thead>
                                <tr>
                                    <th><?php echo e(__('web_app.table.name')); ?></th>
                                    <th><?php echo e(__('web_app.table.phone')); ?></th>
                                    <th><?php echo e(__('web_app.table.group')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $criticalCases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $beneficiary): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><strong><?php echo e($beneficiary->full_name); ?></strong></td>
                                        <td><span><?php echo e($beneficiary->phone ?? '—'); ?></span></td>
                                        <td><span><?php echo e($beneficiary->serviceGroup?->name ?? '—'); ?></span></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <div class="space-y-6">
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label"><?php echo e(__('web_app.dashboard.side_metrics')); ?></p>
                        <h3><?php echo e(__('web_app.dashboard.system_readiness')); ?></h3>
                    </div>
                </div>

                <dl class="app-mini-metrics">
                    <div>
                        <dt><?php echo e(__('web_app.dashboard.stats.open_prayer_requests')); ?></dt>
                        <dd><?php echo e(number_format($secondaryStats['openPrayerRequests'])); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo e(__('web_app.dashboard.stats.medical_files')); ?></dt>
                        <dd><?php echo e(number_format($secondaryStats['medicalFiles'])); ?></dd>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($secondaryStats['users'] !== null): ?>
                        <div>
                            <dt><?php echo e(__('web_app.dashboard.stats.scoped_users')); ?></dt>
                            <dd><?php echo e(number_format($secondaryStats['users'])); ?></dd>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($secondaryStats['serviceGroups'] !== null): ?>
                        <div>
                            <dt><?php echo e(__('web_app.dashboard.stats.service_groups')); ?></dt>
                            <dd><?php echo e(number_format($secondaryStats['serviceGroups'])); ?></dd>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </dl>
            </section>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($todayBirthdays->isNotEmpty()): ?>
                <section class="app-panel">
                    <div class="app-panel-header">
                        <div>
                            <p class="app-section-label"><?php echo e(__('web_app.dashboard.today')); ?></p>
                            <h3>&#x1F382; <?php echo e(__('web_app.dashboard.birthdays')); ?></h3>
                        </div>
                    </div>

                    <div class="app-activity-list">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $todayBirthdays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $beneficiary): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <article class="app-activity-row">
                                <div>
                                    <strong><?php echo e($beneficiary->full_name); ?></strong>
                                    <span><?php echo e($beneficiary->serviceGroup?->name ?? '—'); ?></span>
                                </div>
                                <span class="app-status-pill" style="background:#fef3c7;color:#92400e">
                                    <?php echo e($beneficiary->birth_date?->format('M d')); ?>

                                </span>
                            </article>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </section>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($unvisited->isNotEmpty()): ?>
                <section class="app-panel">
                    <div class="app-panel-header">
                        <div>
                            <p class="app-section-label"><?php echo e(__('web_app.dashboard.attention')); ?></p>
                            <h3>&#x23F0; <?php echo e(__('web_app.dashboard.unvisited')); ?></h3>
                        </div>
                        <a href="<?php echo e(route('app.beneficiaries', ['filter' => 'needs_visit'])); ?>" wire:navigate><?php echo e(__('web_app.actions.view_all')); ?></a>
                    </div>

                    <div class="app-activity-list">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $unvisited; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $beneficiary): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <article class="app-activity-row">
                                <div>
                                    <strong><?php echo e($beneficiary->full_name); ?></strong>
                                    <span><?php echo e($beneficiary->serviceGroup?->name ?? '—'); ?></span>
                                </div>
                                <span class="app-status-pill" style="background:#fee2e2;color:#991b1b">
                                    <?php echo e(__('web_app.dashboard.never_visited')); ?>

                                </span>
                            </article>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </section>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($visitsChart->isNotEmpty()): ?>
        <section class="app-panel">
            <div class="app-panel-header">
                <div>
                    <p class="app-section-label"><?php echo e(__('web_app.dashboard.stats.visits_this_month')); ?></p>
                    <h3><?php echo e(__('web_app.dashboard.visits_chart')); ?></h3>
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500 font-bold"><?php echo e($visitsChart->sum()); ?> total</span>
            </div>

            <div class="pt-6 pb-4 px-2">
                <?php $max = max(1, max($visitsChart->values()->toArray())); ?>
                <div class="space-y-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $visitsChart; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $month => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $pct = ($count / $max) * 100;
                            $barColor = $count >= $max * 0.75 ? '#ef4444' : ($count >= $max * 0.5 ? '#f59e0b' : '#3b82f6');
                        ?>
                        <div class="flex items-center gap-3">
                            <span class="w-12 text-xs font-bold text-gray-500 dark:text-gray-400 text-end flex-shrink-0">
                                <?php echo e(\Carbon\Carbon::createFromFormat('Y-m', $month)->format('M')); ?>

                            </span>
                            <div class="flex-1 h-7 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 relative">
                                <div class="h-full rounded-lg transition-all duration-500"
                                     style="width: <?php echo e(max(4, $pct)); ?>%; background: linear-gradient(135deg, <?php echo e($barColor); ?>, <?php echo e($barColor); ?>dd);">
                                </div>
                            </div>
                            <span class="w-8 text-xs font-bold text-gray-600 dark:text-gray-300 text-end flex-shrink-0"><?php echo e($count); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</section>
<?php /**PATH E:\ministry-system\resources\views/livewire/web-app/dashboard.blade.php ENDPATH**/ ?>