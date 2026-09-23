{{-- Servant theme toggle (light/dark). State is owned by servant.js via
     data-servant-theme-toggle; initial paint is set by the FOUC script
     in servant/layouts/app.blade.php. --}}
<button
    type="button"
    data-servant-theme-toggle
    aria-pressed="false"
    aria-label="{{ __('web_app.actions.theme_toggle') }}"
    {{ $attributes->merge(['class' => 'servant-theme-btn']) }}
>
    <i class="ph ph-moon text-lg icon-moon" aria-hidden="true"></i>
    <i class="ph ph-sun text-lg icon-sun hidden" aria-hidden="true"></i>
</button>
