<div class="waiting-body" wire:poll.60000ms.visible>
    <style>
        .waiting-body {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            color: #1f2937;
        }

        [data-theme='dark'] .waiting-body {
            color: #e2e8f0;
        }

        .waiting-icon {
            font-size: 2.5rem;
            line-height: 1;
        }

        .waiting-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .waiting-hello {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .waiting-status {
            display: inline-block;
            margin: 0 auto;
            padding: 0.35rem 1rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .waiting-status--pending {
            background: #fef3c7;
            color: #92400e;
        }

        .waiting-status--incomplete {
            background: #dbeafe;
            color: #1e40af;
        }

        .waiting-status--approved {
            background: #dcfce7;
            color: #166534;
        }

        .waiting-status--rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .waiting-details {
            margin: 0.25rem 0 0;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            text-align: start;
        }

        .waiting-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.92rem;
            border-bottom: 1px dashed #e7e8ef;
            padding-bottom: 0.4rem;
        }

        [data-theme='dark'] .waiting-row {
            border-color: #334155;
        }

        .waiting-row dt {
            color: #6b7280;
        }

        [data-theme='dark'] .waiting-row dt {
            color: #94a3b8;
        }

        .waiting-row dd {
            margin: 0;
            font-weight: 600;
        }

        .waiting-note {
            margin: 0;
            font-size: 0.9rem;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            text-align: start;
            color: #7c2d12;
        }

        .waiting-intro {
            margin: 0;
            font-size: 0.88rem;
            color: #6b7280;
            line-height: 1.7;
        }

        [data-theme='dark'] .waiting-intro {
            color: #94a3b8;
        }

        .waiting-logout {
            margin-top: 0.5rem;
        }

        .waiting-refresh {
            cursor: pointer;
            border: none;
            background: #0073A3;
            color: #fff;
            border-radius: 0.75rem;
            padding: 0.65rem 1.5rem;
            font: inherit;
            font-weight: 700;
        }

        .waiting-logout-btn {
            cursor: pointer;
            border: 1px solid #e7e8ef;
            background: transparent;
            color: inherit;
            border-radius: 0.75rem;
            padding: 0.6rem 1.5rem;
            font: inherit;
            font-weight: 600;
        }
    </style>

    <div class="waiting-icon" aria-hidden="true">
        {{ $joinRequest && $joinRequest->status === App\Models\JoinRequest::STATUS_APPROVED ? '🎉' : '⏳' }}
    </div>

    <h1 class="waiting-title">{{ __('join_requests.waiting_title') }}</h1>

    <p class="waiting-hello">{{ __('join_requests.waiting_hello', ['name' => auth()->user()?->name]) }}</p>

    @if ($joinRequest)
        <p class="waiting-status waiting-status--{{ $joinRequest->status }}">
            {{ $joinRequest->statusLabel() }}
        </p>

        <dl class="waiting-details">
            <div class="waiting-row">
                <dt>{{ __('join_requests.service_group') }}</dt>
                <dd>{{ $joinRequest->serviceGroup?->name ?? '—' }}</dd>
            </div>
            <div class="waiting-row">
                <dt>{{ __('join_requests.desired_role') }}</dt>
                <dd>{{ $joinRequest->desiredRoleLabel() }}</dd>
            </div>
            <div class="waiting-row">
                <dt>{{ __('join_requests.submitted_at') }}</dt>
                <dd>{{ optional($joinRequest->created_at)->isoFormat('LLL') }}</dd>
            </div>
        </dl>

        @if ($joinRequest->decision_note)
            <p class="waiting-note">
                <strong>{{ __('join_requests.decision_note') }}:</strong>
                {{ $joinRequest->decision_note }}
            </p>
        @endif
    @endif

    <p class="waiting-intro">{{ __('join_requests.waiting_intro') }}</p>

    @if ($joinRequest && $joinRequest->isOpen())
        <button type="button" wire:click="$refresh" class="waiting-refresh">
            <span wire:loading.remove>{{ __('join_requests.refresh_now') }}</span>
            <span wire:loading>{{ __('join_requests.refreshing') }}</span>
        </button>
    @endif

    <form method="POST" action="{{ route('logout') }}" class="waiting-logout">
        @csrf
        <button type="submit" class="waiting-logout-btn">{{ __('join_requests.logout') }}</button>
    </form>
</div>
