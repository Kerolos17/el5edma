<section class="app-page-stack">
    <x-slot:title>{{ $meta['title'] }}</x-slot:title>
    <div class="app-hero-panel">
        <div><h2>{{ $meta['title'] }}</h2></div>
        <div class="app-hero-actions">
            <a href="{{ route('app.dashboard') }}" wire:navigate class="app-primary-button"><i class="ph ph-squares-four" aria-hidden="true"></i> {{ __('web_app.actions.dashboard') }}</a>
            <a href="{{ route('app.beneficiaries') }}" wire:navigate class="app-secondary-button"><i class="ph ph-users-three" aria-hidden="true"></i> {{ __('web_app.actions.beneficiaries') }}</a>
        </div>
    </div>
    <div class="app-stat-grid app-stat-grid-compact">
        @foreach ($stats as $stat)<article class="app-stat-card tone-{{ $stat['tone'] }}"><div><p>{{ $stat['label'] }}</p><strong>{{ number_format($stat['value']) }}</strong></div></article>@endforeach
    </div>
    <section class="app-card-grid">
        @foreach ($reportCards as $card)
            <a href="{{ $card['route'] }}" class="app-panel app-resource-card">
                <div class="app-resource-card-icon"><i class="ph {{ $card['icon'] }}" aria-hidden="true"></i></div>
                <div><h3>{{ $card['title'] }}</h3><p>{{ $card['description'] }}</p></div>
                <span class="app-link-inline">{{ __('web_app.reports.open_report') }} <i class="ph ph-arrow-up-left" aria-hidden="true"></i></span>
            </a>
        @endforeach
    </section>
</section>
