<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" dir="<?php echo e(app()->getLocale() === 'ar' ? 'rtl' : 'ltr'); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e(isset($title) ? $title . ' - ' : ''); ?><?php echo e(__('web_app.brand.name')); ?></title>

    <script>
        (() => {
            const storedTheme = localStorage.getItem('web-app-theme');
            const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
            const theme = storedTheme || (prefersDark ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme;
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <?php if (! $__env->hasRenderedOnce('0b01217c-bdb6-4f85-9e68-cf0c905b3ba1')): $__env->markAsRenderedOnce('0b01217c-bdb6-4f85-9e68-cf0c905b3ba1'); ?>
        <?php
            $manifestPath = public_path('build/manifest.json');
            $viteEntries = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) ?? [] : [];
            $hasWebAppCss = array_key_exists('resources/css/web-app.css', $viteEntries);
            $hasWebAppJs = array_key_exists('resources/js/web-app.js', $viteEntries);
        ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasWebAppCss && $hasWebAppJs): ?>
            <?php echo app('Illuminate\Foundation\Vite')(['resources/css/web-app.css', 'resources/js/web-app.js']); ?>
        <?php else: ?>
            <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php endif; ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>

<body class="web-app-body" x-data="{ drawer: false }">
    <?php
        $appUser = auth()->user();
        $navigationItems = \App\Support\WebAppNavigation::items($appUser);
        $roleLabel = \App\Support\WebAppScope::roleLabel($appUser->role);
        $nextLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
        $nextLocaleLabel = strtoupper($nextLocale);
    ?>

    <div class="app-shell">
        <aside class="app-sidebar" aria-label="<?php echo e(__('web_app.shell.main_navigation')); ?>">
            <div class="app-brand">
                <div class="app-brand-mark">AS</div>
                <div>
                    <p class="app-brand-title"><?php echo e(__('web_app.brand.name')); ?></p>
                    <p class="app-brand-subtitle"><?php echo e($roleLabel); ?></p>
                </div>
            </div>

            <nav class="app-nav">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $navigationItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route($item['route'])); ?>" wire:navigate
                        class="app-nav-item <?php echo e(request()->routeIs($item['route']) ? 'is-active' : ''); ?>">
                        <i class="ph <?php echo e($item['icon']); ?>" aria-hidden="true"></i>
                        <span><?php echo e($item['label']); ?></span>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </nav>

        </aside>

        <div class="app-main">
            <header class="app-topbar" data-user-id="<?php echo e($appUser->id); ?>">
                <div>
                    <button @click="drawer = true" class="app-hamburger lg:hidden" aria-label="<?php echo e(__('web_app.shell.main_navigation')); ?>">
                        <i class="ph ph-list"></i>
                    </button>
                </div>

                <div x-cloak x-show="drawer" @click="drawer = false" class="app-drawer-backdrop lg:hidden" aria-hidden="true"></div>
                <div x-cloak x-show="drawer"
                     x-transition:enter="transition-transform duration-300 ease-out"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition-transform duration-200 ease-in"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="app-drawer lg:hidden">

                    <div class="app-drawer-head">
                        <div style="width:40px;height:40px;border-radius:9999px;overflow:hidden;border:2px solid rgba(148,163,184,0.35);background:#334155;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($appUser->profile_photo_url): ?>
                                <img src="<?php echo e($appUser->profile_photo_url); ?>" alt="" style="width:100%;height:100%;object-fit:cover">
                            <?php else: ?>
                                <span style="font-size:14px;font-weight:800;color:#94a3b8"><?php echo e(mb_substr($appUser->name, 0, 1)); ?></span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <div style="min-width:0">
                            <p style="color:white;font-weight:800;font-size:0.94rem;margin:0;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo e($appUser->name); ?></p>
                            <span style="color:#94a3b8;font-size:0.75rem;font-weight:600"><?php echo e($roleLabel); ?></span>
                        </div>
                        <button @click="drawer = false" class="app-drawer-close" aria-label="<?php echo e(__('web_app.actions.close')); ?>">
                            <i class="ph ph-x"></i>
                        </button>
                    </div>

                    <nav class="app-drawer-nav">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $navigationItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e(route($item['route'])); ?>" wire:navigate @click="drawer = false"
                               class="app-drawer-nav-item <?php echo e(request()->routeIs($item['route']) ? 'is-active' : ''); ?>">
                                <i class="ph <?php echo e($item['icon']); ?>" aria-hidden="true"></i>
                                <span><?php echo e($item['label']); ?></span>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </nav>

                    <div style="padding:0.75rem 1rem 1.25rem">
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit"
                                    style="width:100%;display:flex;align-items:center;gap:0.7rem;padding:0.75rem 1rem;border:0;background:transparent;color:rgba(255,255,255,0.45);font-size:0.88rem;font-weight:700;border-radius:0.75rem;cursor:pointer;transition:all 160ms ease"
                                    onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='rgba(239,68,68,0.8)'"
                                    onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.45)'">
                                <i class="ph ph-sign-out" style="font-size:1.2rem" aria-hidden="true"></i>
                                <?php echo e(__('web_app.actions.logout')); ?>

                            </button>
                        </form>
                    </div>
                </div>

                <form action="<?php echo e(route('app.beneficiaries')); ?>" method="GET" class="app-global-search">
                    <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                    <input
                        type="search"
                        name="q"
                        value="<?php echo e(request('q', '')); ?>"
                        placeholder="<?php echo e(__('web_app.shell.search_placeholder')); ?>"
                        aria-label="<?php echo e(__('web_app.shell.search_label')); ?>">
                </form>

                <div class="app-topbar-actions">
                    <a href="<?php echo e(route('app.dashboard')); ?>" wire:navigate class="app-icon-button" title="<?php echo e(__('web_app.shell.go_dashboard')); ?>">
                        <i class="ph ph-house-line" aria-hidden="true"></i>
                    </a>

                    <button type="button" class="app-icon-button" data-theme-toggle title="<?php echo e(__('web_app.shell.toggle_dark_mode')); ?>">
                        <i class="ph ph-moon" aria-hidden="true" data-theme-dark-icon></i>
                        <i class="ph ph-sun" aria-hidden="true" data-theme-light-icon></i>
                    </button>

                    <button type="submit" form="web-app-language-form" class="app-icon-button app-language-button" title="<?php echo e(__('web_app.shell.switch_language')); ?>">
                        <i class="ph ph-translate" aria-hidden="true"></i>
                        <span><?php echo e($nextLocaleLabel); ?></span>
                    </button>
                    <form id="web-app-language-form" method="POST"
                        action="<?php echo e(route('language.switch', $nextLocale)); ?>"
                        class="hidden">
                        <?php echo csrf_field(); ?>
                    </form>

                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('servant.notifications-bell');

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1452058323-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>

                    <div style="position:relative">
                        <button type="button"
                            onclick="event.stopPropagation();var m=document.getElementById('prof-dd');m.style.display=m.style.display==='block'?'none':'block'"
                            style="display:flex;align-items:center;justify-content:center;border:0;padding:0;background:transparent;cursor:pointer;border-radius:9999px">
                            <div style="width:36px;height:36px;border-radius:9999px;overflow:hidden;border:2px solid #dbe3ee;background:#e5e7eb;display:flex;align-items:center;justify-content:center">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($appUser->profile_photo_url): ?>
                                    <img src="<?php echo e($appUser->profile_photo_url); ?>" alt="" style="width:100%;height:100%;object-fit:cover">
                                <?php else: ?>
                                    <span style="font-size:12px;font-weight:800;color:#9ca3af"><?php echo e(mb_substr($appUser->name, 0, 1)); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </button>
                        <div id="prof-dd" class="prof-dropdown" style="display:none;position:absolute;top:calc(100% + 10px);inset-inline-end:0;min-width:220px;z-index:70;direction:<?php echo e(app()->getLocale() === 'ar' ? 'rtl' : 'ltr'); ?>">
                            <div class="prof-dropdown-head">
                                <p><?php echo e($appUser->name); ?></p>
                                <span><?php echo e($roleLabel); ?></span>
                            </div>
                            <a href="<?php echo e(route('app.profile')); ?>" wire:navigate class="prof-dropdown-item">
                                <i class="ph ph-user-circle" aria-hidden="true"></i>
                                <?php echo e(__('web_app.navigation.profile')); ?>

                            </a>
                            <form method="POST" action="<?php echo e(route('logout')); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="prof-dropdown-item prof-dropdown-item-danger">
                                    <i class="ph ph-sign-out" aria-hidden="true"></i>
                                    <?php echo e(__('web_app.actions.logout')); ?>

                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="app-content">
                <?php echo e($slot); ?>

            </main>
        </div>
    </div>

    <nav class="app-mobile-nav" aria-label="<?php echo e(__('web_app.shell.mobile_navigation')); ?>">
        <?php $mobileItems = array_slice($navigationItems, 0, 4); ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $mobileItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route($item['route'])); ?>" wire:navigate
                class="app-mobile-nav-item <?php echo e(request()->routeIs($item['route']) ? 'is-active' : ''); ?>">
                <i class="ph <?php echo e($item['icon']); ?>" aria-hidden="true"></i>
                <span><?php echo e($item['label']); ?></span>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </nav>

    <?php if (isset($component)) { $__componentOriginal717beba4b7d8165f6fbcaba971be3bb8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal717beba4b7d8165f6fbcaba971be3bb8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.web-app.offline-banner','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('web-app.offline-banner'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal717beba4b7d8165f6fbcaba971be3bb8)): ?>
<?php $attributes = $__attributesOriginal717beba4b7d8165f6fbcaba971be3bb8; ?>
<?php unset($__attributesOriginal717beba4b7d8165f6fbcaba971be3bb8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal717beba4b7d8165f6fbcaba971be3bb8)): ?>
<?php $component = $__componentOriginal717beba4b7d8165f6fbcaba971be3bb8; ?>
<?php unset($__componentOriginal717beba4b7d8165f6fbcaba971be3bb8); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginalc61e1d29d8408a2aac6e5a3135b6b8ea = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc61e1d29d8408a2aac6e5a3135b6b8ea = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.web-app.install-prompt','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('web-app.install-prompt'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc61e1d29d8408a2aac6e5a3135b6b8ea)): ?>
<?php $attributes = $__attributesOriginalc61e1d29d8408a2aac6e5a3135b6b8ea; ?>
<?php unset($__attributesOriginalc61e1d29d8408a2aac6e5a3135b6b8ea); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc61e1d29d8408a2aac6e5a3135b6b8ea)): ?>
<?php $component = $__componentOriginalc61e1d29d8408a2aac6e5a3135b6b8ea; ?>
<?php unset($__componentOriginalc61e1d29d8408a2aac6e5a3135b6b8ea); ?>
<?php endif; ?>

    <script>
        document.addEventListener('click', function () {
            var m = document.getElementById('prof-dd');
            if (m) m.style.display = 'none';
        });
    </script>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>

</html>
<?php /**PATH E:\ministry-system\resources\views/web-app/layouts/app.blade.php ENDPATH**/ ?>