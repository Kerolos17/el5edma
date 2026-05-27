<section class="app-page-stack">
     <?php $__env->slot('title', null, []); ?> <?php echo e($meta['title']); ?> <?php $__env->endSlot(); ?>

    <div class="app-hero-panel">
        <div>
            <h2><?php echo e($meta['title']); ?></h2>
        </div>
        <div class="app-hero-actions">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\Beneficiary::class)): ?>
                <button type="button" wire:click="openBeneficiaryForm" class="app-primary-button">
                    <i class="ph ph-user-plus" aria-hidden="true"></i>
                    <?php echo e(__('web_app.actions.add_beneficiary')); ?>

                </button>
            <?php endif; ?>
            <a href="<?php echo e(route('app.visits')); ?>" wire:navigate class="app-secondary-button">
                <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                <?php echo e(__('web_app.actions.visits')); ?>

            </a>
        </div>
    </div>

    <div class="app-stat-grid app-stat-grid-compact">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $stats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <article class="app-stat-card tone-<?php echo e($stat['tone']); ?>">
                <div>
                    <p><?php echo e($stat['label']); ?></p>
                    <strong><?php echo e(number_format($stat['value'])); ?></strong>
                </div>
            </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <section class="app-panel app-toolbar-panel">
        <div class="app-toolbar">
            <label class="app-search-field">
                <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="<?php echo e(__('web_app.resources.search_placeholder', ['title' => $meta['title']])); ?>">
            </label>
            <div class="app-chip-row" role="tablist">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $filters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <button type="button" wire:click="$set('filter', '<?php echo e($item['value']); ?>')"
                        class="app-filter-chip <?php echo e($filter === $item['value'] ? 'is-active' : ''); ?>">
                        <?php echo e($item['label']); ?>

                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="app-panel">
        <div class="app-panel-header">
            <div>
                <p class="app-section-label"><?php echo e(__('web_app.resources.operational_view')); ?></p>
                <h3><?php echo e($meta['title']); ?></h3>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($records instanceof \Illuminate\Contracts\Pagination\Paginator): ?>
                <span class="app-muted-badge"><?php echo e(trans_choice('web_app.resources.items_count', $records->total(), ['count' => number_format($records->total())])); ?></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div class="app-table-wrap">
            <table class="app-table">
                <thead>
                    <tr>
                        <th></th>
                        <th><?php echo e(__('web_app.table.beneficiary')); ?></th>
                        <th><?php echo e(__('web_app.table.group')); ?></th>
                        <th><?php echo e(__('web_app.table.servant')); ?></th>
                        <th><?php echo e(__('web_app.table.visits')); ?></th>
                        <th><?php echo e(__('web_app.table.status')); ?></th>
                        <th><?php echo e(__('web_app.table.actions')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $records; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="!pr-0 !w-12">
                                <a href="<?php echo e(route('app.beneficiary-profile', $record->id)); ?>" wire:navigate>
                                    <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0 flex items-center justify-center">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->photo_url): ?>
                                            <img src="<?php echo e($record->photo_url); ?>" alt="" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <span class="text-sm font-bold text-gray-400 dark:text-gray-500"><?php echo e(mb_substr($record->full_name, 0, 1)); ?></span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo e(route('app.beneficiary-profile', $record->id)); ?>" wire:navigate class="font-bold text-decoration-none">
                                    <strong><?php echo e($record->full_name); ?></strong>
                                    <span><?php echo e($record->code ?: __('web_app.fallback.no_code')); ?></span>
                                </a>
                            </td>
                            <td><?php echo e($record->serviceGroup?->name ?? __('web_app.fallback.unassigned_feminine')); ?></td>
                            <td><?php echo e($record->assignedServant?->name ?? __('web_app.fallback.unassigned')); ?></td>
                            <td><?php echo e(number_format($record->visits_count)); ?></td>
                            <td><span class="app-status-pill tone-slate"><?php echo e($record->status ? __("beneficiaries.{$record->status}") : __('beneficiaries.active')); ?></span></td>
                            <td>
                                <div class="app-inline-actions">
                                    <a href="<?php echo e(route('app.beneficiary-profile', $record->id)); ?>" wire:navigate class="app-link-inline">
                                        <i class="ph ph-eye" aria-hidden="true"></i>
                                        <?php echo e(__('web_app.actions.view')); ?>

                                    </a>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $record)): ?>
                                        <button type="button" wire:click="openBeneficiaryForm(<?php echo e($record->id); ?>)" class="app-link-inline">
                                            <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                                            <?php echo e(__('web_app.actions.edit')); ?>

                                        </button>
                                    <?php endif; ?>
                                    <button type="button" wire:click="openVisitForm(<?php echo e($record->id); ?>)" class="app-link-inline">
                                        <i class="ph ph-plus" aria-hidden="true"></i>
                                        <?php echo e(__('web_app.actions.visit')); ?>

                                    </button>
                                    <button type="button" wire:click="openPrayerForm(<?php echo e($record->id); ?>)" class="app-link-inline">
                                        <i class="ph ph-hands-praying" aria-hidden="true"></i>
                                        <?php echo e(__('web_app.actions.prayer')); ?>

                                    </button>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\MedicalFile::class)): ?>
                                        <button type="button" wire:click="openMedicalFileForm(<?php echo e($record->id); ?>)" class="app-link-inline">
                                            <i class="ph ph-upload-simple" aria-hidden="true"></i>
                                            <?php echo e(__('web_app.actions.medical_file')); ?>

                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-gray-500 dark:text-gray-400">
                                <?php echo e(__('web_app.resources.empty_table')); ?>

                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="app-mobile-list">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $records; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <article class="app-mobile-card">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="<?php echo e(route('app.beneficiary-profile', $record->id)); ?>" wire:navigate>
                            <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0 flex items-center justify-center">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->photo_url): ?>
                                    <img src="<?php echo e($record->photo_url); ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-sm font-bold text-gray-400"><?php echo e(mb_substr($record->full_name, 0, 1)); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </a>
                        <div class="min-w-0">
                            <a href="<?php echo e(route('app.beneficiary-profile', $record->id)); ?>" wire:navigate class="font-bold text-decoration-none">
                                <strong><?php echo e($record->full_name); ?></strong>
                            </a>
                            <p class="truncate"><?php echo e($record->serviceGroup?->name ?? __('web_app.fallback.unassigned_feminine')); ?></p>
                        </div>
                    </div>
                    <div class="app-mobile-meta">
                        <span><?php echo e($record->assignedServant?->name ?? __('web_app.fallback.unassigned')); ?></span>
                        <span><?php echo e(trans_choice('web_app.resources.visits_count', $record->visits_count, ['count' => number_format($record->visits_count)])); ?></span>
                    </div>
                    <div class="app-mobile-actions">
                        <a href="<?php echo e(route('app.beneficiary-profile', $record->id)); ?>" wire:navigate class="app-link-inline">
                            <i class="ph ph-eye" aria-hidden="true"></i>
                            <?php echo e(__('web_app.actions.view')); ?>

                        </a>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $record)): ?>
                            <button type="button" wire:click="openBeneficiaryForm(<?php echo e($record->id); ?>)" class="app-link-inline">
                                <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                                <?php echo e(__('web_app.actions.edit')); ?>

                            </button>
                        <?php endif; ?>
                        <button type="button" wire:click="openVisitForm(<?php echo e($record->id); ?>)" class="app-link-inline">
                            <i class="ph ph-plus" aria-hidden="true"></i>
                            <?php echo e(__('web_app.actions.visit')); ?>

                        </button>
                        <button type="button" wire:click="openPrayerForm(<?php echo e($record->id); ?>)" class="app-link-inline">
                            <i class="ph ph-hands-praying" aria-hidden="true"></i>
                            <?php echo e(__('web_app.actions.prayer')); ?>

                        </button>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\MedicalFile::class)): ?>
                            <button type="button" wire:click="openMedicalFileForm(<?php echo e($record->id); ?>)" class="app-link-inline">
                                <i class="ph ph-upload-simple" aria-hidden="true"></i>
                                <?php echo e(__('web_app.actions.medical_file')); ?>

                            </button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="app-empty-state">
                    <i class="ph ph-users-three" aria-hidden="true"></i>
                    <p><?php echo e(__('web_app.resources.empty_table')); ?></p>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($records instanceof \Illuminate\Contracts\Pagination\Paginator): ?>
            <div class="app-pagination-wrap">
                <?php echo e($records->onEachSide(1)->links()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php echo $__env->make('livewire.web-app.partials.modals.index', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</section><?php /**PATH E:\ministry-system\resources\views/livewire/web-app/beneficiaries-page.blade.php ENDPATH**/ ?>