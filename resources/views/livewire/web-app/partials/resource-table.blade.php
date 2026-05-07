<section class="app-panel">
    <div class="app-panel-header">
        <div>
            <p class="app-section-label">عرض تشغيلي</p>
            <h3>{{ $meta['title'] }}</h3>
        </div>
        @if ($records instanceof \Illuminate\Contracts\Pagination\Paginator)
            <span class="app-muted-badge">{{ number_format($records->total()) }} عنصر</span>
        @endif
    </div>

    <div class="app-table-wrap">
        <table class="app-table">
            <thead>
                <tr>
                    @include('livewire.web-app.partials.resource-headers')
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        @include('livewire.web-app.partials.resource-row', ['record' => $record])
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">لا توجد بيانات للعرض.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="app-mobile-list">
        @forelse ($records as $record)
            <article class="app-mobile-card">
                @include('livewire.web-app.partials.resource-mobile-card', ['record' => $record])
            </article>
        @empty
        @endforelse
    </div>

    @if ($records instanceof \Illuminate\Contracts\Pagination\Paginator)
        <div class="app-pagination-wrap">
            {{ $records->onEachSide(1)->links() }}
        </div>
    @endif
</section>
