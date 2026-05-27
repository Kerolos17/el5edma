<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showServiceGroupForm): ?>
    <div class="app-modal-backdrop" wire:click="closeServiceGroupForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="<?php echo e(__('web_app.forms.service_group.manage')); ?>">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div><p class="app-section-label"><?php echo e(__('web_app.forms.service_group.section')); ?></p><h3><?php echo e($editingServiceGroupId ? __('web_app.forms.service_group.edit_title') : __('web_app.forms.service_group.create_title')); ?></h3></div>
                <button type="button" wire:click="closeServiceGroupForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="app-form-grid">
                <label class="app-form-field"><span><?php echo e(__('service_groups.name')); ?></span><input type="text" wire:model="serviceGroupName" placeholder="<?php echo e(__('web_app.forms.placeholders.service_group_name')); ?>"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['serviceGroupName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                <label class="app-form-field">
                    <span><?php echo e(__('service_groups.service_leader')); ?></span>
                    <select wire:model="serviceGroupServiceLeaderId" <?php if(auth()->user()->isServiceLeader()): echo 'disabled'; endif; ?>>
                        <option value=""><?php echo e(__('web_app.fallback.unassigned')); ?></option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $serviceGroupServiceLeaderOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($id); ?>"><?php echo e($name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['serviceGroupServiceLeaderId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </label>
                <label class="app-form-field">
                    <span><?php echo e(__('service_groups.leader')); ?></span>
                    <select wire:model="serviceGroupLeaderId" <?php if(! $editingServiceGroupId): echo 'disabled'; endif; ?>>
                        <option value=""><?php echo e(__('web_app.fallback.unassigned')); ?></option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $serviceGroupLeaderOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($id); ?>"><?php echo e($name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['serviceGroupLeaderId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </label>
                <label class="app-form-field app-form-field-full"><span><?php echo e(__('service_groups.description')); ?></span><textarea wire:model="serviceGroupDescription" rows="4" placeholder="<?php echo e(__('web_app.forms.placeholders.service_group_description')); ?>"></textarea><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['serviceGroupDescription'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                <div class="app-check-grid app-form-field-full"><label class="app-check-row"><input type="checkbox" wire:model="serviceGroupIsActive"><span><?php echo e(__('web_app.forms.service_group.active')); ?></span></label></div>
            </div>
            <div class="app-modal-actions">
                <button type="button" wire:click="closeServiceGroupForm" class="app-secondary-button"><?php echo e(__('web_app.actions.cancel')); ?></button>
                <button type="button" wire:click="saveServiceGroup" wire:loading.attr="disabled" class="app-primary-button"><?php echo e(__('web_app.forms.service_group.save')); ?></button>
            </div>
        </div>
    </section>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH E:\ministry-system\resources\views/livewire/web-app/partials/modals/service-group-form.blade.php ENDPATH**/ ?>