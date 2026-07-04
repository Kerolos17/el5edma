<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('web-app.layouts.app')]
class AuditLogsPage extends Component
{
    use WithPagination;

    #[Url(except: 'all')]
    public string $filterAction = 'all';

    #[Url(except: 'all')]
    public string $filterModel = 'all';

    public ?int $viewingLogId = null;

    public function viewLog(int $id): void
    {
        $this->viewingLogId = $id;
    }

    public function closeView(): void
    {
        $this->viewingLogId = null;
    }

    public function render(): View
    {
        $actor = auth()->user();
        abort_unless($actor->can('viewAny', AuditLog::class), 403);

        $query = AuditLog::query()->with('user');

        if ($this->filterAction !== 'all') {
            $query->where('action', $this->filterAction);
        }

        if ($this->filterModel !== 'all') {
            $modelMap = [
                'beneficiary'     => Beneficiary::class,
                'visit'           => Visit::class,
                'user'            => User::class,
                'service_group'   => ServiceGroup::class,
                'scheduled_visit' => ScheduledVisit::class,
            ];
            $class = $modelMap[$this->filterModel] ?? null;
            if ($class) {
                $query->where('model_type', $class);
            }
        }

        $stats = [
            ['label' => __('audit_logs.title'), 'value' => number_format((clone $query)->count()), 'tone' => 'blue'],
            ['label' => __('audit_logs.created'), 'value' => number_format((clone $query)->where('action', 'created')->count()), 'tone' => 'emerald'],
            ['label' => __('audit_logs.updated'), 'value' => number_format((clone $query)->where('action', 'updated')->count()), 'tone' => 'amber'],
            ['label' => __('audit_logs.deleted'), 'value' => number_format((clone $query)->where('action', 'deleted')->count()), 'tone' => 'rose'],
        ];

        $viewingLog = $this->viewingLogId
            ? AuditLog::with('user')->whereKey($this->viewingLogId)->first()
            : null;

        $records = $query->latest('created_at')->paginate(20);

        return view('livewire.web-app.audit-logs-page', [
            'stats'      => $stats,
            'records'    => $records,
            'viewingLog' => $viewingLog,
        ]);
    }
}
