<section class="app-page-stack">
     <?php $__env->slot('title', null, []); ?> <?php echo e($beneficiary->full_name); ?> <?php $__env->endSlot(); ?>

    
    <div class="app-hero-panel">
        <div class="flex items-start gap-4 sm:gap-6 flex-wrap sm:flex-nowrap">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden ring-4 ring-white/30 flex-shrink-0 bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->photo_url): ?>
                    <img src="<?php echo e($beneficiary->photo_url); ?>" alt="<?php echo e($beneficiary->full_name); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-2xl sm:text-3xl font-bold text-gray-400 dark:text-gray-500"><?php echo e(mb_substr($beneficiary->full_name, 0, 1)); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xl sm:text-2xl font-bold"><?php echo e($beneficiary->full_name); ?></h2>
                    <span class="app-muted-badge"><?php echo e($beneficiary->code ?: __('web_app.fallback.no_code')); ?></span>
                    <span class="app-muted-badge <?php switch($beneficiary->status): case ('inactive'): ?> bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 <?php case ('suspended'): ?> bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 <?php default: ?> bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 <?php endswitch; ?>">
                        <?php echo e($beneficiary->status ? __("beneficiaries.{$beneficiary->status}") : __('beneficiaries.active')); ?>

                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    <?php echo e($beneficiary->serviceGroup?->name ?? __('web_app.fallback.unassigned_feminine')); ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->assignedServant): ?>
                        &middot; <?php echo e($beneficiary->assignedServant->name); ?>

                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </p>
                <div class="flex gap-2 mt-3 flex-wrap">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->whatsapp_url): ?>
                        <a href="<?php echo e($beneficiary->whatsapp_url); ?>" target="_blank" class="app-secondary-button !text-sm !py-1.5">
                            <i class="ph-fill ph-whatsapp-logo" aria-hidden="true"></i>
                            WhatsApp
                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->phone): ?>
                        <a href="tel:<?php echo e($beneficiary->phone); ?>" class="app-secondary-button !text-sm !py-1.5">
                            <i class="ph ph-phone" aria-hidden="true"></i>
                            <?php echo e(__('web_app.table.phone')); ?>

                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->google_maps_url): ?>
                        <a href="<?php echo e($beneficiary->google_maps_url); ?>" target="_blank" class="app-secondary-button !text-sm !py-1.5">
                            <i class="ph ph-map-pin" aria-hidden="true"></i>
                            <?php echo e(__('web_app.table.address')); ?>

                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
            <div class="flex gap-2 flex-shrink-0 self-start">
                <a href="<?php echo e(route('reports.beneficiary.pdf', $beneficiary)); ?>" target="_blank" class="app-secondary-button !text-sm !py-1.5">
                    <i class="ph ph-file-pdf" aria-hidden="true"></i>
                    <?php echo e(__('web_app.actions.download_pdf')); ?>

                </a>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $beneficiary)): ?>
                    <button type="button" wire:click="openBeneficiaryForm(<?php echo e($beneficiary->id); ?>)" class="app-primary-button !text-sm !py-1.5">
                        <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                        <?php echo e(__('web_app.actions.edit')); ?>

                    </button>
                <?php endif; ?>
                <a href="<?php echo e(route('app.beneficiaries')); ?>" wire:navigate class="app-secondary-button !text-sm">
                    <i class="ph ph-arrow-right" aria-hidden="true"></i>
                    <?php echo e(__('web_app.actions.beneficiaries')); ?>

                </a>
            </div>
        </div>
    </div>

    
    <div class="app-stat-grid app-stat-grid-2x2">
        <div class="app-stat-card tone-blue">
            <div class="app-stat-icon"><i class="ph ph-cake" aria-hidden="true"></i></div>
            <div>
                <p><?php echo e(__('web_app.table.birth_date')); ?></p>
                <strong><?php echo e($beneficiary->birth_date?->format('Y-m-d') ?? '—'); ?></strong>
            </div>
        </div>
        <div class="app-stat-card tone-emerald">
            <div class="app-stat-icon"><i class="ph ph-gender-intersex" aria-hidden="true"></i></div>
            <div>
                <p><?php echo e(__('web_app.table.gender')); ?></p>
                <strong><?php echo e($beneficiary->gender ? __("beneficiaries.{$beneficiary->gender}") : '—'); ?></strong>
            </div>
        </div>
        <div class="app-stat-card tone-amber">
            <div class="app-stat-icon"><i class="ph ph-map-pin-area" aria-hidden="true"></i></div>
            <div>
                <p><?php echo e(__('web_app.table.area')); ?></p>
                <strong title="<?php echo e($beneficiary->area); ?>"><?php echo e($beneficiary->area ?: '—'); ?></strong>
            </div>
        </div>
        <div class="app-stat-card tone-rose">
            <div class="app-stat-icon"><i class="ph ph-heartbeat" aria-hidden="true"></i></div>
            <div>
                <p><?php echo e(__('web_app.table.health')); ?></p>
                <span class="app-status-pill mt-1 <?php switch($beneficiary->health_status): case ('critical'): ?> bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 <?php case ('needs_follow_up'): ?> bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 <?php default: ?> bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 <?php endswitch; ?>">
                    <?php echo e($beneficiary->health_status ? __("visits.{$beneficiary->health_status}") : __('visits.good')); ?>

                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">

            
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label"><?php echo e(__('web_app.table.contact_title')); ?></p>
                        <h3><?php echo e(__('web_app.table.contact_info')); ?></h3>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo e(__('web_app.table.phone')); ?></dt>
                            <dd class="mt-0.5 font-bold"><?php echo e($beneficiary->phone ?: '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold">WhatsApp</dt>
                            <dd class="mt-0.5 font-bold"><?php echo e($beneficiary->whatsapp ?: '—'); ?></dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo e(__('web_app.table.address')); ?></dt>
                            <dd class="mt-0.5 break-words"><?php echo e($beneficiary->address_text ?: '—'); ?></dd>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->area || $beneficiary->governorate): ?>
                                <dd class="text-xs text-gray-500 mt-1"><?php echo e(collect([$beneficiary->area, $beneficiary->governorate])->filter()->join(', ')); ?></dd>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->google_maps_url): ?>
                                <dd class="mt-1.5">
                                    <a href="<?php echo e($beneficiary->google_maps_url); ?>" target="_blank" class="text-blue-600 dark:text-blue-400 text-xs font-bold hover:underline">
                                        <i class="ph ph-map-pin" aria-hidden="true"></i> <?php echo e(__('web_app.table.view_location')); ?>

                                    </a>
                                </dd>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->facebook_url || $beneficiary->instagram_url): ?>
                            <div class="sm:col-span-2 flex gap-4">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->facebook_url): ?>
                                    <a href="<?php echo e($beneficiary->facebook_url); ?>" target="_blank" class="app-link-inline">
                                        <i class="ph ph-facebook-logo" aria-hidden="true"></i> Facebook
                                    </a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->instagram_url): ?>
                                    <a href="<?php echo e($beneficiary->instagram_url); ?>" target="_blank" class="app-link-inline">
                                        <i class="ph ph-instagram-logo" aria-hidden="true"></i> Instagram
                                    </a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dl>
                </div>
            </section>

            
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label"><?php echo e(__('web_app.table.guardian_title')); ?></p>
                        <h3><?php echo e(__('web_app.table.family_info')); ?></h3>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo e(__('web_app.table.guardian')); ?></dt>
                            <dd class="mt-0.5 font-bold"><?php echo e($beneficiary->guardian_name ?: '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo e(__('web_app.table.guardian_phone')); ?></dt>
                            <dd class="mt-0.5 font-bold"><?php echo e($beneficiary->guardian_phone ?: '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo e(__('web_app.table.guardian_relation')); ?></dt>
                            <dd class="mt-0.5"><?php echo e($beneficiary->guardian_relation ?: '—'); ?></dd>
                        </div>
                        <div></div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo e(__('web_app.table.father_status')); ?></dt>
                            <dd class="mt-0.5"><?php echo e($beneficiary->father_status ?: '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo e(__('web_app.table.mother_status')); ?></dt>
                            <dd class="mt-0.5"><?php echo e($beneficiary->mother_status ?: '—'); ?></dd>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->siblings_count !== null): ?>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo e(__('web_app.table.siblings')); ?></dt>
                                <dd class="mt-0.5 font-bold"><?php echo e($beneficiary->siblings_count); ?><?php echo e($beneficiary->siblings_note ? " ({$beneficiary->siblings_note})" : ''); ?></dd>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dl>
                </div>
            </section>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->disability_type || $beneficiary->health_status || $beneficiary->doctor_name || $beneficiary->medical_notes): ?>
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label"><?php echo e(__('web_app.table.medical_title')); ?></p>
                        <h3><?php echo e(__('web_app.table.health_records')); ?></h3>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 text-sm">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->disability_type): ?>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo e(__('web_app.table.disability')); ?></dt>
                                <dd class="mt-0.5 font-bold"><?php echo e($beneficiary->disability_type); ?><?php echo e($beneficiary->disability_degree ? " ({$beneficiary->disability_degree})" : ''); ?></dd>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->doctor_name): ?>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo e(__('web_app.table.doctor')); ?></dt>
                                <dd class="mt-0.5"><?php echo e($beneficiary->doctor_name); ?></dd>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->hospital_name): ?>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo e(__('web_app.table.hospital')); ?></dt>
                                <dd class="mt-0.5"><?php echo e($beneficiary->hospital_name); ?></dd>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->last_medical_update): ?>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo e(__('web_app.table.last_update')); ?></dt>
                                <dd class="mt-0.5"><?php echo e($beneficiary->last_medical_update->format('Y-m-d')); ?></dd>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->medical_notes): ?>
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo e(__('web_app.table.medical_notes')); ?></dt>
                                <dd class="mt-0.5 whitespace-pre-wrap"><?php echo e($beneficiary->medical_notes); ?></dd>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dl>
                </div>
            </section>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->activeMedications->isNotEmpty()): ?>
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label"><?php echo e(__('web_app.table.medications')); ?></p>
                        <h3><?php echo e(__('web_app.table.medication_list')); ?></h3>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="space-y-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $beneficiary->activeMedications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $medication): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div>
                                    <p class="font-bold text-sm"><?php echo e($medication->name); ?></p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($medication->dosage): ?>
                                        <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($medication->dosage); ?></p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <span class="text-xs text-gray-400 dark:text-gray-500"><?php echo e($medication->created_at->format('Y-m-d')); ?></span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <div class="space-y-6">

            
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label"><?php echo e(__('web_app.table.activity')); ?></p>
                        <h3><?php echo e(__('web_app.table.visits')); ?></h3>
                    </div>
                    <a href="<?php echo e(route('app.visits')); ?>" wire:navigate><?php echo e(__('web_app.actions.view_all')); ?></a>
                </div>
                <div class="p-4">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $beneficiary->visits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $visit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <article class="flex items-start gap-3 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                            <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                <i class="ph ph-clipboard-text text-blue-600 dark:text-blue-400" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold"><?php echo e($visit->createdBy?->name ?? __('web_app.dashboard.unknown_user')); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo e($visit->visit_date?->format('Y-m-d')); ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($visit->type): ?>
                                        &middot; <?php echo e(__("visits.{$visit->type}")); ?>

                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </p>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="app-empty-state !py-8">
                            <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                            <p><?php echo e(__('web_app.dashboard.empty_visits')); ?></p>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </section>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->financial_status): ?>
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label"><?php echo e(__('web_app.table.financial')); ?></p>
                        <h3><?php echo e(__('web_app.table.financial_status')); ?></h3>
                    </div>
                </div>
                <div class="p-4 sm:p-6 text-sm">
                    <p class="font-bold"><?php echo e($beneficiary->financial_status ? __("beneficiaries.{$beneficiary->financial_status}") : '—'); ?></p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->financial_notes): ?>
                        <p class="mt-2 text-gray-500 dark:text-gray-400"><?php echo e($beneficiary->financial_notes); ?></p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </section>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($beneficiary->prayerRequests->isNotEmpty()): ?>
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label"><?php echo e(__('web_app.table.prayers')); ?></p>
                        <h3><?php echo e(__('web_app.table.prayer_list')); ?></h3>
                    </div>
                    <a href="<?php echo e(route('app.prayer-requests')); ?>" wire:navigate><?php echo e(__('web_app.actions.view_all')); ?></a>
                </div>
                <div class="p-4">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $beneficiary->prayerRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prayer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <article class="flex items-start gap-3 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                            <div class="w-9 h-9 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                                <i class="ph ph-hands-praying text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold"><?php echo e($prayer->title); ?></p>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($prayer->body): ?>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2"><?php echo e($prayer->body); ?></p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </section>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <?php echo $__env->make('livewire.web-app.partials.modals.beneficiary-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</section>
<?php /**PATH E:\ministry-system\resources\views/livewire/web-app/beneficiary-profile-page.blade.php ENDPATH**/ ?>