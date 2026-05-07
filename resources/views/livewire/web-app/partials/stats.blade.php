<div class="app-stat-grid app-stat-grid-compact">
    @foreach ($stats as $stat)
        <article class="app-stat-card tone-{{ $stat['tone'] }}">
            <div>
                <p>{{ $stat['label'] }}</p>
                <strong>{{ number_format($stat['value']) }}</strong>
            </div>
        </article>
    @endforeach
</div>
