{{--
    Web-App Empty State
    Usage:
        <x-web-app.empty-state
            icon="ph-users-three"
            :message="__('web_app.resources.empty_table')"
            :action-label="__('web_app.actions.add_beneficiary')"
            action-wire-click="openBeneficiaryForm"
        />
    All attributes except `icon` and `message` are optional.
--}}
<div class="app-empty-state" role="status">
    <i class="ph {{ $icon }}" aria-hidden="true"></i>
    <p>{{ $message }}</p>
    @if (isset($actionLabel) && isset($actionWireClick))
        <button type="button" wire:click="{{ $actionWireClick }}" class="app-secondary-button" style="margin-top:0.75rem">
            {{ $actionLabel }}
        </button>
    @elseif (isset($actionLabel) && isset($actionHref))
        <a href="{{ $actionHref }}" wire:navigate class="app-secondary-button" style="margin-top:0.75rem">
            {{ $actionLabel }}
        </a>
    @endif
</div>
