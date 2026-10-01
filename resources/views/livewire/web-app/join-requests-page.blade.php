<section class="app-page-stack">
    <x-slot:title>{{ __('join_requests.title') }}</x-slot:title>

    <div class="app-hero-panel">
        <div>
            <h2>{{ __('join_requests.title') }}</h2>
        </div>
    </div>

    <div class="app-stat-grid app-stat-grid-compact">
        @foreach ($stats as $stat)
            <article class="app-stat-card">
                <div>
                    <p>{{ $stat['label'] }}</p>
                    <strong>{{ number_format($stat['value']) }}</strong>
                </div>
            </article>
        @endforeach
    </div>

    <section class="app-panel app-toolbar-panel">
        <div class="app-toolbar">
            <label class="app-search-field">
                <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                <input wire:model.live.debounce.300ms="search" type="search" enterkeyhint="search"
                    placeholder="{{ __('join_requests.search_placeholder') }}">
            </label>
            <div class="app-chip-row" role="tablist">
                @foreach ([
                    'open' => __('join_requests.filters.open'),
                    'approved' => __('join_requests.statuses.approved'),
                    'rejected' => __('join_requests.statuses.rejected'),
                    'suspended' => __('join_requests.filters.suspended'),
                    'all' => __('join_requests.filters.all'),
                ] as $value => $label)
                    <button type="button" wire:click="$set('filter', '{{ $value }}')"
                        class="app-filter-chip {{ $filter === $value ? 'is-active' : '' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="app-panel">
        <div class="app-panel-header">
            <div>
                <p class="app-section-label">{{ __('join_requests.review_queue') }}</p>
                <h3>{{ __('join_requests.title') }}</h3>
            </div>
            @if ($records instanceof \Illuminate\Contracts\Pagination\Paginator)
                <span class="app-muted-badge">{{ trans_choice('web_app.resources.items_count', $records->total(), ['count' => number_format($records->total())]) }}</span>
            @endif
        </div>

        <x-web-app.list-loading />

        <div class="app-table-wrap" wire:loading.attr="aria-busy" wire:target="search,filter">
            <table class="app-table" aria-label="{{ __('join_requests.title') }}">
                <thead>
                    <tr>
                        <th scope="col">{{ __('web_app.table.user') }}</th>
                        <th scope="col">{{ __('web_app.table.phone') }}</th>
                        <th scope="col">{{ __('join_requests.service_group') }}</th>
                        <th scope="col">{{ __('join_requests.desired_role') }}</th>
                        <th scope="col">{{ __('join_requests.submitted_at') }}</th>
                        <th scope="col">{{ __('web_app.table.status') }}</th>
                        <th scope="col">{{ __('web_app.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>
                                <strong>{{ $record->user?->name }}</strong>
                                <br>
                                <small class="app-muted">{{ $record->user?->email }}</small>
                                @if ($record->decision_note && $record->status !== 'approved')
                                    <br>
                                    <small class="app-muted">{{ __('join_requests.decision_note') }}: {{ $record->decision_note }}</small>
                                @endif
                            </td>
                            <td dir="ltr">{{ $record->user?->phone ?? '—' }}</td>
                            <td>{{ $record->serviceGroup?->name ?? '—' }}</td>
                            <td>{{ $record->desiredRoleLabel() }}</td>
                            <td>{{ optional($record->created_at)->isoFormat('LL') }}</td>
                            <td>
                                @php
                                    $statusTone = match ($record->status) {
                                        App\Models\JoinRequest::STATUS_APPROVED => $record->user?->is_active ? 'tone-emerald' : 'tone-amber',
                                        App\Models\JoinRequest::STATUS_REJECTED => 'tone-rose',
                                        default => 'tone-sky',
                                    };
                                    $statusLabel = $record->status === App\Models\JoinRequest::STATUS_APPROVED && ! $record->user?->is_active
                                        ? __('join_requests.filters.suspended')
                                        : $record->statusLabel();
                                @endphp
                                <span class="app-status-pill {{ $statusTone }}">{{ $statusLabel }}</span>
                            </td>
                            <td>
                                <div class="app-inline-actions">
                                    @if ($record->isOpen() && auth()->user()->can('review', $record))
                                        <button type="button" wire:click="openReview({{ $record->id }}, 'approve')"
                                            class="app-link-inline">
                                            <i class="ph ph-check-circle" aria-hidden="true"></i>
                                            {{ __('join_requests.actions.review') }}
                                        </button>
                                    @elseif ($record->status === App\Models\JoinRequest::STATUS_APPROVED && auth()->user()->can('suspend', $record))
                                        @if ($record->user?->is_active)
                                            <button type="button" wire:click="toggleSuspension({{ $record->id }})"
                                                class="app-link-inline app-link-danger"
                                                wire:confirm="{{ __('join_requests.confirms.suspend') }}">
                                                <i class="ph ph-pause-circle" aria-hidden="true"></i>
                                                {{ __('join_requests.actions.suspend') }}
                                            </button>
                                        @else
                                            <button type="button" wire:click="toggleSuspension({{ $record->id }})"
                                                class="app-link-inline">
                                                <i class="ph ph-play-circle" aria-hidden="true"></i>
                                                {{ __('join_requests.actions.reactivate') }}
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="app-empty-state">{{ __('join_requests.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="app-pagination">
            {{ $records->links() }}
        </div>
    </section>

    @php $reviewing = $reviewingId ? App\Models\JoinRequest::with('user')->find($reviewingId) : null; @endphp

    @if ($reviewing)
        <x-web-app.modal :show="true" :wide="true" :title="$reviewing->user?->name"
            :description="__('join_requests.singular')" close="closeReview">
            <p class="app-muted">{{ __('join_requests.review_summary', [
                'group' => $reviewing->serviceGroup?->name ?? '—',
                'role' => $reviewing->desiredRoleLabel(),
                'date' => optional($reviewing->created_at)->isoFormat('LL'),
                'email' => $reviewing->user?->email ?? '—',
                'phone' => $reviewing->user?->phone ?? '—',
            ]) }}</p>

            <div class="app-chip-row" role="tablist" style="margin-top:1rem">
                <button type="button" wire:click="$set('reviewAction', 'approve')"
                    class="app-filter-chip {{ $reviewAction === 'approve' ? 'is-active' : '' }}">
                    {{ __('join_requests.modal.approve_tab') }}</button>
                <button type="button" wire:click="$set('reviewAction', 'reject')"
                    class="app-filter-chip {{ $reviewAction === 'reject' ? 'is-active' : '' }}">
                    {{ __('join_requests.modal.reject_tab') }}</button>
                <button type="button" wire:click="$set('reviewAction', 'changes')"
                    class="app-filter-chip {{ $reviewAction === 'changes' ? 'is-active' : '' }}">
                    {{ __('join_requests.modal.changes_tab') }}</button>
            </div>

            @if ($reviewAction === 'approve')
                <div class="app-form-grid">
                    <label class="app-form-field" for="review-role">
                        <span>{{ __('join_requests.final_role') }}</span>
                        <select id="review-role" wire:model="reviewRole">
                            @foreach ($roleOptions as $option)
                                <option value="{{ $option }}">{{ __("users.roles.{$option}") }}</option>
                            @endforeach
                        </select>
                        @error('reviewRole') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="app-form-field" for="review-group">
                        <span>{{ __('join_requests.final_service_group') }}</span>
                        <select id="review-group" wire:model="reviewGroupId">
                            @foreach ($groupOptions as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </select>
                        @error('reviewGroupId') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
            @else
                <div class="app-form-stack">
                    <label class="app-form-field app-form-field-full" for="review-note">
                        <span>{{ $reviewAction === 'reject' ? __('join_requests.modal.reject_reason') : __('join_requests.modal.changes_note') }}</span>
                        <textarea id="review-note" wire:model="reviewNote" rows="3"></textarea>
                        @error('reviewNote') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
            @endif

            @error('review') <small>{{ $message }}</small> @enderror

            <x-slot:actions>
                <button type="button" wire:click="closeReview" class="app-secondary-button">
                    {{ __('web_app.actions.cancel') }}
                </button>
                <button type="button" wire:click="submitReview" wire:loading.attr="disabled" class="app-primary-button">
                    {{ $reviewAction === 'approve' ? __('join_requests.modal.approve_confirm') : __('web_app.actions.save') }}
                </button>
            </x-slot:actions>
        </x-web-app.modal>
    @endif
</section>
