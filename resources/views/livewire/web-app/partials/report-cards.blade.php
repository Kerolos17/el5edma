<section class="app-card-grid">
    @foreach ($reportCards as $card)
        <a href="{{ $card['route'] }}" class="app-panel app-resource-card">
            <div class="app-resource-card-icon">
                <i class="ph {{ $card['icon'] }}" aria-hidden="true"></i>
            </div>
            <div>
                <h3>{{ $card['title'] }}</h3>
                <p>{{ $card['description'] }}</p>
            </div>
            <span class="app-link-inline">
                فتح التقرير
                <i class="ph ph-arrow-up-left" aria-hidden="true"></i>
            </span>
        </a>
    @endforeach
</section>
