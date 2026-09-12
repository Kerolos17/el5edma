@props([
    'show' => false,
    'wide' => false,
    'title' => '',
    'titleId' => null,
    'description' => '',
    'close' => '',
])
@if ($show)
    <div class="app-modal-backdrop" wire:click="{{ $close }}" aria-hidden="true"></div>
    <section
        class="app-modal-sheet"
        role="dialog"
        aria-modal="true"
        @if ($titleId)
            aria-labelledby="{{ $titleId }}"
        @else
            aria-label="{{ $title }}"
        @endif
    >
        <div class="app-modal-panel{{ $wide ? ' app-modal-panel-wide' : '' }}" tabindex="-1">
            <div class="app-modal-header">
                <div>
                    @if ($description)<p class="app-section-label">{{ $description }}</p>@endif
                    <h3 @if ($titleId) id="{{ $titleId }}" @endif>{{ $title }}</h3>
                </div>
                <button type="button" wire:click="{{ $close }}" class="app-icon-button" aria-label="{{ __('web_app.actions.close') }}">
                    <i class="ph ph-x" aria-hidden="true"></i>
                </button>
            </div>
            {{ $slot }}
            @isset($actions)
                <div class="app-modal-actions">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </section>
@endif
