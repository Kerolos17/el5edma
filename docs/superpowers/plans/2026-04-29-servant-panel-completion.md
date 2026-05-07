# Servant Panel Completion Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete the Servant Panel PWA by adding ScheduledVisits screen, PrayerRequests screen, full test suite for all servant components, and verifying the notification bell integration.

**Architecture:** All new Livewire components follow the established patterns in `App\Livewire\Servant\*` — `#[Locked]` on model-bound IDs, dual ownership scope (`assigned_servant_id OR service_group_id`), `dispatch('toast', ...)` for feedback, `#[On('*-saved')] refresh()` for cross-component reactivity. New routes are appended to `routes/servant.php` inside the existing middleware group. Tests follow PHPUnit Feature test style using `RefreshDatabase` and the `CreatesTestUsers` trait.

**Tech Stack:** Laravel 12 · Livewire 3 · Alpine.js · Tailwind CSS (servant.css design system) · PHPUnit 11 · Phosphor Icons · RTL Arabic

---

## File Structure

### Phase 1 — ScheduledVisits Screen

| File | Action | Responsibility |
|------|--------|----------------|
| `app/Livewire/Servant/ScheduledVisitList.php` | Create | List + cancel action for assigned scheduled visits |
| `resources/views/livewire/servant/scheduled-visit-list.blade.php` | Create | Mobile-first RTL blade view |
| `routes/servant.php` | Modify | Add `GET /servant/scheduled-visits` route |
| `tests/Feature/Servant/ScheduledVisitListLivewireTest.php` | Create | Scoping, display, cancel, 403 checks |

### Phase 2 — PrayerRequests Screen

| File | Action | Responsibility |
|------|--------|----------------|
| `app/Livewire/Servant/PrayerRequestList.php` | Create | List + inline create form for prayer requests |
| `resources/views/livewire/servant/prayer-request-list.blade.php` | Create | Mobile-first RTL blade view |
| `routes/servant.php` | Modify | Add `GET /servant/prayer-requests` route |
| `tests/Feature/Servant/PrayerRequestListLivewireTest.php` | Create | Scoping, create, validation, 403 checks |

### Phase 3 — Test Suite for Existing Servant Components

| File | Action | Responsibility |
|------|--------|----------------|
| `tests/Feature/Servant/ServantPanelAccessTest.php` | Create | Middleware: auth guard, role guard, active guard |
| `tests/Feature/Servant/DashboardLivewireTest.php` | Create | Stats correctness, visit-saved refresh, scoping |
| `tests/Feature/Servant/BeneficiaryListLivewireTest.php` | Create | Search, filters, ownership scope |
| `tests/Feature/Servant/BeneficiaryDetailLivewireTest.php` | Create | Mount 403, prayer requests shown, visitThis dispatch |
| `tests/Feature/Servant/VisitListLivewireTest.php` | Create | Filters, pagination, ownership scope |
| `tests/Feature/Servant/CreateVisitWizardLivewireTest.php` | Create | Step validation, submit, ownership check, offline |
| `tests/Feature/Servant/OfflineVisitSyncControllerTest.php` | Create | 201 save, 409 conflict, 403 wrong owner |

### Phase 4 — Notification Bell Verification

| File | Action | Responsibility |
|------|--------|----------------|
| `routes/channels.php` | Verify/fix | Ensure `user.{id}` channel auth is correct |
| `tests/Feature/Servant/NotificationsBellLivewireTest.php` | Create | Load, markRead, markAllRead, scope |

---

## Phase 1: ScheduledVisits Screen

### Task 1: ScheduledVisitList Livewire Component (PHP)

**Files:**
- Create: `app/Livewire/Servant/ScheduledVisitList.php`

- [ ] **Step 1: Write the failing test first**

Create `tests/Feature/Servant/ScheduledVisitListLivewireTest.php`:

```php
<?php

namespace Tests\Feature\Servant;

use App\Livewire\Servant\ScheduledVisitList;
use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ScheduledVisitListLivewireTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    #[Test]
    public function servant_can_see_their_scheduled_visits(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        $mine  = ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
            'scheduled_date'      => now()->addDay(),
        ]);
        $other = ScheduledVisit::factory()->create([
            'assigned_servant_id' => $this->createServant($group)->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
            'scheduled_date'      => now()->addDay(),
        ]);

        Livewire::actingAs($servant)
            ->test(ScheduledVisitList::class)
            ->assertSee($b->full_name)
            ->assertViewHas('scheduledVisits', fn ($sv) => $sv->contains('id', $mine->id))
            ->assertViewHas('scheduledVisits', fn ($sv) => ! $sv->contains('id', $other->id));
    }

    #[Test]
    public function servant_can_cancel_their_scheduled_visit(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);

        $sv = ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
            'scheduled_date'      => now()->addDay(),
        ]);

        Livewire::actingAs($servant)
            ->test(ScheduledVisitList::class)
            ->call('cancel', $sv->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('scheduled_visits', ['id' => $sv->id, 'status' => 'cancelled']);
    }

    #[Test]
    public function servant_cannot_cancel_another_servants_scheduled_visit(): void
    {
        $group    = ServiceGroup::factory()->create();
        $servant1 = $this->createServant($group);
        $servant2 = $this->createServant($group);
        $b        = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        $sv = ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant2->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
        ]);

        Livewire::actingAs($servant1)
            ->test(ScheduledVisitList::class)
            ->call('cancel', $sv->id)
            ->assertForbidden();
    }

    #[Test]
    public function filter_upcoming_shows_only_future_pending_visits(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);

        $upcoming = ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
            'scheduled_date'      => now()->addDays(3),
        ]);
        $past = ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
            'scheduled_date'      => now()->subDay(),
        ]);

        Livewire::actingAs($servant)
            ->test(ScheduledVisitList::class)
            ->set('filter', 'upcoming')
            ->assertViewHas('scheduledVisits', fn ($sv) => $sv->contains('id', $upcoming->id))
            ->assertViewHas('scheduledVisits', fn ($sv) => ! $sv->contains('id', $past->id));
    }
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
cd E:/ministry-system && php artisan test tests/Feature/Servant/ScheduledVisitListLivewireTest.php 2>&1 | head -30
```

Expected: `FAIL` — class `App\Livewire\Servant\ScheduledVisitList` not found.

- [ ] **Step 3: Create the ScheduledVisitList Livewire component**

Create `app/Livewire/Servant/ScheduledVisitList.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use App\Models\ScheduledVisit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('servant.layouts.app')]
#[Title('الزيارات المجدولة')]
class ScheduledVisitList extends Component
{
    #[Url(except: 'upcoming')]
    public string $filter = 'upcoming';

    public function cancel(int $id): void
    {
        $sv = ScheduledVisit::where('id', $id)
            ->where('assigned_servant_id', auth()->id())
            ->firstOrFail();

        abort_unless($sv->status === 'pending', 403);

        $sv->update(['status' => 'cancelled']);

        $this->dispatch('toast', message: 'تم إلغاء الزيارة المجدولة', type: 'success');
    }

    public function updatedFilter(): void {}

    public function render()
    {
        $user = auth()->user();

        $query = ScheduledVisit::where('assigned_servant_id', $user->id)
            ->with('beneficiary');

        $scheduledVisits = match ($this->filter) {
            'upcoming' => (clone $query)
                ->where('status', 'pending')
                ->where('scheduled_date', '>=', now()->toDateString())
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->get(),
            'past'     => (clone $query)
                ->where(fn ($q) => $q
                    ->where('scheduled_date', '<', now()->toDateString())
                    ->orWhere('status', 'completed')
                )
                ->orderByDesc('scheduled_date')
                ->limit(50)
                ->get(),
            default    => (clone $query)
                ->orderByDesc('scheduled_date')
                ->get(),
        };

        return view('livewire.servant.scheduled-visit-list', compact('scheduledVisits'));
    }
}
```

- [ ] **Step 4: Run tests again**

```bash
cd E:/ministry-system && php artisan test tests/Feature/Servant/ScheduledVisitListLivewireTest.php 2>&1 | head -40
```

Expected: Fails with "view not found" — that's fine, blade is next.

- [ ] **Step 5: Create the blade view**

Create `resources/views/livewire/servant/scheduled-visit-list.blade.php`:

```blade
<div class="px-4 pt-6 pb-32 lg:pb-10 space-y-5">

    {{-- Page Title --}}
    <div class="reveal-card">
        <h1 class="text-xl font-bold text-teal-900">الزيارات المجدولة</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $scheduledVisits->count() }} زيارة</p>
    </div>

    {{-- Filter Chips --}}
    <div class="flex gap-2 overflow-x-auto pb-1 reveal-card"
         style="animation-delay: 0.06s; scrollbar-width: none;"
         role="group" aria-label="فلتر الزيارات المجدولة">
        @foreach([['upcoming','القادمة'], ['past','السابقة'], ['all','الكل']] as [$val, $label])
            <button wire:click="$set('filter', '{{ $val }}')"
                    class="radio-chip flex-shrink-0 {{ $filter === $val ? 'selected' : '' }}"
                    aria-pressed="{{ $filter === $val ? 'true' : 'false' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Skeleton --}}
    <div wire:loading.delay class="space-y-3" aria-hidden="true">
        @for ($i = 0; $i < 4; $i++)
            <div class="skeleton-shimmer rounded-2xl" style="height:88px;"></div>
        @endfor
    </div>

    {{-- List --}}
    <div wire:loading.remove class="space-y-3">
        @forelse($scheduledVisits as $sv)
            <div class="s-card card-lift rounded-2xl px-4 py-3 flex items-start gap-3"
                 role="article"
                 aria-label="زيارة مجدولة {{ $sv->beneficiary?->full_name ?? '' }}">

                {{-- Date column --}}
                <div class="text-center flex-shrink-0 w-12" aria-hidden="true">
                    <p class="text-2xl font-bold text-teal-700 leading-none" style="font-family: var(--font-accent);">
                        {{ $sv->scheduled_date->format('d') }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $sv->scheduled_date->locale('ar')->isoFormat('MMM') }}
                    </p>
                </div>

                <div class="w-px self-stretch bg-gray-100 flex-shrink-0" aria-hidden="true"></div>

                <div class="flex-1 min-w-0">
                    <p class="font-bold text-teal-900 text-sm truncate">
                        {{ $sv->beneficiary?->full_name ?? 'محذوف' }}
                    </p>

                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        @if($sv->scheduled_time)
                            <span class="text-xs text-gray-500">
                                <i class="ph ph-clock text-xs" aria-hidden="true"></i>
                                {{ \Carbon\Carbon::parse($sv->scheduled_time)->format('g:i A') }}
                            </span>
                        @endif
                        <span @class([
                            'badge-pill text-xs px-2 py-0.5',
                            'badge-info'     => $sv->status === 'pending',
                            'badge-success'  => $sv->status === 'completed',
                            'badge-critical' => $sv->status === 'cancelled',
                        ])>
                            @match($sv->status)
                                'pending'   => 'قيد الانتظار',
                                'completed' => 'مكتملة',
                                'cancelled' => 'ملغاة',
                                default     => $sv->status
                            @endmatch
                        </span>
                    </div>

                    @if($sv->notes)
                        <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $sv->notes }}</p>
                    @endif
                </div>

                {{-- Cancel action --}}
                @if($sv->status === 'pending')
                    <button wire:click="cancel({{ $sv->id }})"
                            wire:confirm="هل تريد إلغاء هذه الزيارة المجدولة؟"
                            class="flex-shrink-0 w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center
                                   hover:bg-red-100 transition-colors"
                            aria-label="إلغاء الزيارة">
                        <i class="ph ph-x text-red-500 text-base" aria-hidden="true"></i>
                    </button>
                @endif
            </div>
        @empty
            <x-ui.empty-state
                icon="ph-calendar-blank"
                message="{{ $filter === 'upcoming' ? 'لا توجد زيارات مجدولة قادمة' : 'لا توجد زيارات' }}"
            />
        @endforelse
    </div>

</div>
```

- [ ] **Step 6: Register the route**

Modify `routes/servant.php` — add one line inside the group after the visits route:

```php
Route::get('/scheduled-visits', \App\Livewire\Servant\ScheduledVisitList::class)->name('scheduled-visits');
```

- [ ] **Step 7: Add bottom-nav link**

In `resources/views/components/servant/bottom-nav.blade.php`, add a nav item for scheduled visits. Find the existing nav items block and add:

```blade
<a href="{{ route('servant.scheduled-visits') }}" wire:navigate
   class="nav-btn {{ request()->routeIs('servant.scheduled-visits') ? 'active' : '' }}"
   aria-label="الزيارات المجدولة">
    <i class="ph-fill ph-calendar-check text-xl" aria-hidden="true"></i>
    <span class="text-[10px] font-semibold mt-0.5">مجدولة</span>
</a>
```

- [ ] **Step 8: Run all tests to verify passing**

```bash
cd E:/ministry-system && php artisan test tests/Feature/Servant/ScheduledVisitListLivewireTest.php --verbose 2>&1
```

Expected: 4 tests pass.

- [ ] **Step 9: Commit**

```bash
cd E:/ministry-system && git add app/Livewire/Servant/ScheduledVisitList.php resources/views/livewire/servant/scheduled-visit-list.blade.php routes/servant.php resources/views/components/servant/bottom-nav.blade.php tests/Feature/Servant/ScheduledVisitListLivewireTest.php && git commit -m "feat: add ScheduledVisitList screen to servant panel"
```

---

## Phase 2: PrayerRequests Screen

### Task 2: PrayerRequestList Livewire Component (PHP)

**Files:**
- Create: `app/Livewire/Servant/PrayerRequestList.php`
- Create: `resources/views/livewire/servant/prayer-request-list.blade.php`
- Modify: `routes/servant.php`
- Create: `tests/Feature/Servant/PrayerRequestListLivewireTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Servant/PrayerRequestListLivewireTest.php`:

```php
<?php

namespace Tests\Feature\Servant;

use App\Livewire\Servant\PrayerRequestList;
use App\Models\Beneficiary;
use App\Models\PrayerRequest;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class PrayerRequestListLivewireTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    #[Test]
    public function servant_sees_prayer_requests_for_their_beneficiaries(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $mine  = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id, 'service_group_id' => $group->id]);
        $other = Beneficiary::factory()->create(['service_group_id' => ServiceGroup::factory()->create()->id]);

        $prMine  = PrayerRequest::factory()->create(['beneficiary_id' => $mine->id,  'status' => 'open', 'created_by' => $servant->id]);
        $prOther = PrayerRequest::factory()->create(['beneficiary_id' => $other->id, 'status' => 'open', 'created_by' => $servant->id]);

        Livewire::actingAs($servant)
            ->test(PrayerRequestList::class)
            ->assertViewHas('prayerRequests', fn ($prs) => $prs->contains('id', $prMine->id))
            ->assertViewHas('prayerRequests', fn ($prs) => ! $prs->contains('id', $prOther->id));
    }

    #[Test]
    public function servant_can_create_prayer_request_for_their_beneficiary(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id, 'service_group_id' => $group->id]);

        Livewire::actingAs($servant)
            ->test(PrayerRequestList::class)
            ->set('beneficiaryId', $b->id)
            ->set('title', 'صلاة لأجل الشفاء')
            ->set('body', 'يحتاج المخدوم دعاء خاص')
            ->call('save')
            ->assertDispatched('toast')
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('prayer_requests', [
            'beneficiary_id' => $b->id,
            'title'          => 'صلاة لأجل الشفاء',
            'status'         => 'open',
            'created_by'     => $servant->id,
        ]);
    }

    #[Test]
    public function servant_cannot_create_prayer_request_for_other_beneficiary(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $other   = Beneficiary::factory()->create(['service_group_id' => ServiceGroup::factory()->create()->id]);

        Livewire::actingAs($servant)
            ->test(PrayerRequestList::class)
            ->set('beneficiaryId', $other->id)
            ->set('title', 'محاولة اختراق')
            ->set('body', 'نص التجربة')
            ->call('save')
            ->assertForbidden();
    }

    #[Test]
    public function title_is_required_and_max_255_chars(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id, 'service_group_id' => $group->id]);

        Livewire::actingAs($servant)
            ->test(PrayerRequestList::class)
            ->set('beneficiaryId', $b->id)
            ->set('title', '')
            ->set('body', 'نص')
            ->call('save')
            ->assertHasErrors(['title' => 'required']);

        Livewire::actingAs($servant)
            ->test(PrayerRequestList::class)
            ->set('beneficiaryId', $b->id)
            ->set('title', str_repeat('أ', 256))
            ->set('body', 'نص')
            ->call('save')
            ->assertHasErrors(['title' => 'max']);
    }

    #[Test]
    public function filter_open_shows_only_open_requests(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id, 'service_group_id' => $group->id]);

        $open     = PrayerRequest::factory()->create(['beneficiary_id' => $b->id, 'status' => 'open',     'created_by' => $servant->id]);
        $answered = PrayerRequest::factory()->create(['beneficiary_id' => $b->id, 'status' => 'answered', 'created_by' => $servant->id]);

        Livewire::actingAs($servant)
            ->test(PrayerRequestList::class)
            ->set('filter', 'open')
            ->assertViewHas('prayerRequests', fn ($prs) => $prs->contains('id', $open->id))
            ->assertViewHas('prayerRequests', fn ($prs) => ! $prs->contains('id', $answered->id));
    }
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
cd E:/ministry-system && php artisan test tests/Feature/Servant/PrayerRequestListLivewireTest.php 2>&1 | head -20
```

Expected: `FAIL` — class not found.

- [ ] **Step 3: Create PrayerRequestList Livewire component**

Create `app/Livewire/Servant/PrayerRequestList.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Servant;

use App\Models\Beneficiary;
use App\Models\PrayerRequest;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('servant.layouts.app')]
#[Title('طلبات الصلاة')]
class PrayerRequestList extends Component
{
    #[Url(except: 'open')]
    public string $filter = 'open';

    public bool $showForm = false;

    // Form fields
    #[Locked]
    public ?int $beneficiaryId = null;

    public string $title = '';
    public string $body  = '';

    public function openForm(): void
    {
        $this->reset(['beneficiaryId', 'title', 'body']);
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->reset(['beneficiaryId', 'title', 'body']);
    }

    public function save(): void
    {
        $this->validate([
            'beneficiaryId' => ['required', 'integer'],
            'title'         => ['required', 'string', 'max:255'],
            'body'          => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless(
            $this->ownedBeneficiaryQuery()->where('id', $this->beneficiaryId)->exists(),
            403
        );

        PrayerRequest::create([
            'beneficiary_id' => $this->beneficiaryId,
            'title'          => $this->title,
            'body'           => $this->body ?: null,
            'status'         => 'open',
            'created_by'     => auth()->id(),
        ]);

        $this->showForm = false;
        $this->reset(['beneficiaryId', 'title', 'body']);
        $this->dispatch('toast', message: 'تم حفظ طلب الصلاة', type: 'success');
    }

    public function updatedFilter(): void {}

    public function render()
    {
        $user = auth()->user();

        $prayerRequests = PrayerRequest::whereHas('beneficiary', $this->ownedBeneficiaryScope($user))
            ->with('beneficiary')
            ->when($this->filter !== 'all', fn ($q) => $q->where('status', $this->filter))
            ->latest()
            ->get();

        $myBeneficiaries = $this->ownedBeneficiaryQuery()->orderBy('full_name')->get(['id', 'full_name']);

        return view('livewire.servant.prayer-request-list', compact('prayerRequests', 'myBeneficiaries'));
    }

    private function ownedBeneficiaryQuery(): Builder
    {
        return Beneficiary::query()->where(
            fn ($q) => $q
                ->where('assigned_servant_id', auth()->id())
                ->when(
                    auth()->user()->service_group_id,
                    fn ($q2) => $q2->orWhere('service_group_id', auth()->user()->service_group_id),
                )
        );
    }

    private function ownedBeneficiaryScope($user): \Closure
    {
        return fn ($q) => $q
            ->where('assigned_servant_id', $user->id)
            ->when(
                $user->service_group_id,
                fn ($q2) => $q2->orWhere('service_group_id', $user->service_group_id),
            );
    }
}
```

- [ ] **Step 4: Create the blade view**

Create `resources/views/livewire/servant/prayer-request-list.blade.php`:

```blade
<div class="px-4 pt-6 pb-32 lg:pb-10 space-y-5">

    {{-- Page Title + FAB --}}
    <div class="reveal-card flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-teal-900">طلبات الصلاة</h1>
            <p class="text-sm text-gray-400 mt-0.5">{{ $prayerRequests->count() }} طلب</p>
        </div>
        <button wire:click="openForm"
                class="w-11 h-11 rounded-2xl gradient-deep flex items-center justify-center shadow-lg text-white"
                aria-label="إضافة طلب صلاة">
            <i class="ph-bold ph-plus text-lg" aria-hidden="true"></i>
        </button>
    </div>

    {{-- Filter Chips --}}
    <div class="flex gap-2 overflow-x-auto pb-1 reveal-card"
         style="animation-delay:0.06s; scrollbar-width:none;"
         role="group" aria-label="فلتر طلبات الصلاة">
        @foreach([['open','مفتوحة'], ['answered','مجابة'], ['closed','مغلقة'], ['all','الكل']] as [$val, $label])
            <button wire:click="$set('filter', '{{ $val }}')"
                    class="radio-chip flex-shrink-0 {{ $filter === $val ? 'selected' : '' }}"
                    aria-pressed="{{ $filter === $val ? 'true' : 'false' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Create Form --}}
    @if($showForm)
        <div class="s-card rounded-2xl p-5 space-y-4 reveal-card" role="region" aria-label="نموذج طلب صلاة جديد">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-teal-900">طلب صلاة جديد</h2>
                <button wire:click="closeForm" class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center"
                        aria-label="إغلاق النموذج">
                    <i class="ph ph-x text-gray-500" aria-hidden="true"></i>
                </button>
            </div>

            {{-- Beneficiary select --}}
            <div>
                <label class="block text-sm font-semibold text-teal-900 mb-1.5">المخدوم <span class="text-red-500">*</span></label>
                <select wire:model="beneficiaryId"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-teal-400 focus:outline-none"
                        aria-required="true">
                    <option value="">-- اختر مخدوماً --</option>
                    @foreach($myBeneficiaries as $b)
                        <option value="{{ $b->id }}">{{ $b->full_name }}</option>
                    @endforeach
                </select>
                @error('beneficiaryId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Title --}}
            <div>
                <label class="block text-sm font-semibold text-teal-900 mb-1.5">الموضوع <span class="text-red-500">*</span></label>
                <input wire:model.live.debounce.300ms="title"
                       type="text"
                       placeholder="موضوع الصلاة"
                       maxlength="255"
                       class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-400 focus:outline-none"
                       aria-required="true" />
                @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Body --}}
            <div>
                <label class="block text-sm font-semibold text-teal-900 mb-1.5">التفاصيل</label>
                <textarea wire:model="body"
                          rows="3"
                          placeholder="تفاصيل إضافية (اختياري)"
                          maxlength="2000"
                          class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-400 focus:outline-none resize-none"></textarea>
            </div>

            <button wire:click="save"
                    wire:loading.attr="disabled"
                    class="w-full py-3 rounded-2xl gradient-deep text-white font-bold text-sm shadow-lg
                           disabled:opacity-60 transition-opacity">
                <span wire:loading.remove wire:target="save">حفظ طلب الصلاة</span>
                <span wire:loading wire:target="save">جاري الحفظ...</span>
            </button>
        </div>
    @endif

    {{-- Skeleton --}}
    <div wire:loading.delay class="space-y-3" aria-hidden="true">
        @for ($i = 0; $i < 4; $i++)
            <div class="skeleton-shimmer rounded-2xl" style="height:80px;"></div>
        @endfor
    </div>

    {{-- Prayer Request Cards --}}
    <div wire:loading.remove class="space-y-3">
        @forelse($prayerRequests as $pr)
            <div class="s-card card-lift rounded-2xl px-4 py-3" role="article">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-teal-900 text-sm truncate">{{ $pr->title }}</p>
                        <p class="text-xs text-teal-500 mt-0.5">{{ $pr->beneficiary?->full_name ?? '' }}</p>
                        @if($pr->body)
                            <p class="text-xs text-gray-500 mt-1.5 line-clamp-2">{{ $pr->body }}</p>
                        @endif
                    </div>
                    <span @class([
                        'badge-pill text-xs px-2 py-0.5 flex-shrink-0',
                        'badge-info'    => $pr->status === 'open',
                        'badge-success' => $pr->status === 'answered',
                        'bg-gray-100 text-gray-500' => $pr->status === 'closed',
                    ])>
                        @match($pr->status)
                            'open'     => 'مفتوح',
                            'answered' => 'مجاب',
                            'closed'   => 'مغلق',
                            default    => $pr->status
                        @endmatch
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    <i class="ph ph-clock text-xs" aria-hidden="true"></i>
                    {{ $pr->created_at->locale('ar')->diffForHumans() }}
                </p>
            </div>
        @empty
            <x-ui.empty-state
                icon="ph-hands-praying"
                message="{{ $filter === 'open' ? 'لا توجد طلبات صلاة مفتوحة' : 'لا توجد طلبات' }}"
            />
        @endforelse
    </div>

</div>
```

- [ ] **Step 5: Register the route**

In `routes/servant.php`, add inside the group:

```php
Route::get('/prayer-requests', \App\Livewire\Servant\PrayerRequestList::class)->name('prayer-requests');
```

- [ ] **Step 6: Run all prayer request tests**

```bash
cd E:/ministry-system && php artisan test tests/Feature/Servant/PrayerRequestListLivewireTest.php --verbose 2>&1
```

Expected: 5 tests pass.

- [ ] **Step 7: Ensure PrayerRequest model has factory**

Check if `database/factories/PrayerRequestFactory.php` exists:

```bash
ls E:/ministry-system/database/factories/PrayerRequestFactory.php 2>&1
```

If missing, create it:

```php
<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrayerRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory(),
            'title'          => $this->faker->sentence(4),
            'body'           => $this->faker->optional()->paragraph(),
            'status'         => 'open',
            'created_by'     => User::factory(),
            'answered_at'    => null,
        ];
    }
}
```

- [ ] **Step 8: Commit**

```bash
cd E:/ministry-system && git add app/Livewire/Servant/PrayerRequestList.php resources/views/livewire/servant/prayer-request-list.blade.php routes/servant.php tests/Feature/Servant/PrayerRequestListLivewireTest.php database/factories/PrayerRequestFactory.php && git commit -m "feat: add PrayerRequestList screen to servant panel"
```

---

## Phase 3: Test Suite for Existing Servant Components

### Task 3: ServantPanelAccessTest

**Files:**
- Create: `tests/Feature/Servant/ServantPanelAccessTest.php`

- [ ] **Step 1: Create the test**

```php
<?php

namespace Tests\Feature\Servant;

use App\Models\ServiceGroup;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ServantPanelAccessTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    #[Test]
    public function unauthenticated_user_is_redirected_from_servant_dashboard(): void
    {
        $this->get(route('servant.dashboard'))->assertRedirect();
    }

    #[Test]
    public function inactive_servant_is_logged_out_and_redirected(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group, ['is_active' => false]);

        $this->actingAs($servant)
            ->get(route('servant.dashboard'))
            ->assertRedirect();
    }

    #[Test]
    public function active_servant_can_access_dashboard(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $this->actingAs($servant)
            ->get(route('servant.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function active_family_leader_can_access_servant_panel(): void
    {
        $group  = ServiceGroup::factory()->create();
        $leader = $this->createFamilyLeader($group);

        $this->actingAs($leader)
            ->get(route('servant.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function active_service_leader_can_access_servant_panel(): void
    {
        $leader = $this->createServiceLeader();

        $this->actingAs($leader)
            ->get(route('servant.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function active_super_admin_can_access_servant_panel(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get(route('servant.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function servant_redirect_from_root_servant_url(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $this->actingAs($servant)
            ->get('/servant')
            ->assertRedirect(route('servant.dashboard'));
    }
}
```

- [ ] **Step 2: Run the test**

```bash
cd E:/ministry-system && php artisan test tests/Feature/Servant/ServantPanelAccessTest.php --verbose 2>&1
```

Expected: 7 tests pass.

- [ ] **Step 3: Commit**

```bash
cd E:/ministry-system && git add tests/Feature/Servant/ServantPanelAccessTest.php && git commit -m "test: add ServantPanelAccessTest for middleware guards"
```

---

### Task 4: DashboardLivewireTest

**Files:**
- Create: `tests/Feature/Servant/DashboardLivewireTest.php`

- [ ] **Step 1: Create the test**

```php
<?php

namespace Tests\Feature\Servant;

use App\Livewire\Servant\Dashboard;
use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class DashboardLivewireTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    #[Test]
    public function dashboard_shows_correct_beneficiary_count(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        Beneficiary::factory()->count(3)->create(['assigned_servant_id' => $servant->id]);
        Beneficiary::factory()->create(); // out of scope

        Livewire::actingAs($servant)
            ->test(Dashboard::class)
            ->assertViewHas('myBeneficiariesCount', 3);
    }

    #[Test]
    public function dashboard_shows_visits_this_month(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);

        Visit::factory()->count(2)->create([
            'created_by'   => $servant->id,
            'beneficiary_id' => $b->id,
            'visit_date'   => now(),
        ]);
        Visit::factory()->create([
            'created_by'   => $servant->id,
            'beneficiary_id' => $b->id,
            'visit_date'   => now()->subMonth(),
        ]);

        Livewire::actingAs($servant)
            ->test(Dashboard::class)
            ->assertViewHas('visitsThisMonth', 2);
    }

    #[Test]
    public function dashboard_counts_upcoming_scheduled_visits(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);

        ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
            'scheduled_date'      => now()->addDay(),
        ]);
        ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'completed',
            'scheduled_date'      => now()->addDay(),
        ]);

        Livewire::actingAs($servant)
            ->test(Dashboard::class)
            ->assertViewHas('scheduledCount', 1);
    }

    #[Test]
    public function visit_saved_event_triggers_refresh(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $component = Livewire::actingAs($servant)->test(Dashboard::class);
        $initialCount = $component->viewData('visitsThisMonth');

        Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);
        $b = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);
        Visit::factory()->create(['created_by' => $servant->id, 'beneficiary_id' => $b->id, 'visit_date' => now()]);

        $component->dispatch('visit-saved')
            ->assertViewHas('visitsThisMonth', $initialCount + 1);
    }
}
```

- [ ] **Step 2: Check if ScheduledVisit has a factory**

```bash
ls E:/ministry-system/database/factories/ScheduledVisitFactory.php 2>&1
```

If missing, create `database/factories/ScheduledVisitFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledVisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'beneficiary_id'      => Beneficiary::factory(),
            'assigned_servant_id' => User::factory(),
            'scheduled_date'      => $this->faker->dateTimeBetween('now', '+30 days'),
            'scheduled_time'      => $this->faker->time('H:i:s'),
            'notes'               => $this->faker->optional()->sentence(),
            'status'              => 'pending',
            'created_by'          => User::factory(),
        ];
    }
}
```

- [ ] **Step 3: Run tests**

```bash
cd E:/ministry-system && php artisan test tests/Feature/Servant/DashboardLivewireTest.php --verbose 2>&1
```

Expected: 4 tests pass.

- [ ] **Step 4: Commit**

```bash
cd E:/ministry-system && git add tests/Feature/Servant/DashboardLivewireTest.php database/factories/ScheduledVisitFactory.php database/factories/PrayerRequestFactory.php && git commit -m "test: add DashboardLivewireTest + missing factories"
```

---

### Task 5: BeneficiaryListLivewireTest + VisitListLivewireTest

**Files:**
- Create: `tests/Feature/Servant/BeneficiaryListLivewireTest.php`
- Create: `tests/Feature/Servant/VisitListLivewireTest.php`

- [ ] **Step 1: Create BeneficiaryListLivewireTest**

```php
<?php

namespace Tests\Feature\Servant;

use App\Livewire\Servant\BeneficiaryList;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class BeneficiaryListLivewireTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    #[Test]
    public function servant_only_sees_beneficiaries_from_their_group(): void
    {
        $group1  = ServiceGroup::factory()->create();
        $group2  = ServiceGroup::factory()->create();
        $servant = $this->createServant($group1);

        $inScope  = Beneficiary::factory()->create(['service_group_id' => $group1->id]);
        $outScope = Beneficiary::factory()->create(['service_group_id' => $group2->id]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryList::class)
            ->assertViewHas('beneficiaries', fn ($b) => $b->contains('id', $inScope->id))
            ->assertViewHas('beneficiaries', fn ($b) => ! $b->contains('id', $outScope->id));
    }

    #[Test]
    public function search_filters_by_name(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        Beneficiary::factory()->create(['full_name' => 'مريم سمير',   'service_group_id' => $group->id]);
        Beneficiary::factory()->create(['full_name' => 'يوسف إبراهيم', 'service_group_id' => $group->id]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryList::class)
            ->set('search', 'مريم')
            ->assertViewHas('beneficiaries', fn ($b) => $b->count() === 1 && $b->first()->full_name === 'مريم سمير');
    }

    #[Test]
    public function filter_mine_shows_only_assigned_beneficiaries(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $other   = $this->createServant($group);

        $mine    = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id, 'service_group_id' => $group->id]);
        $notMine = Beneficiary::factory()->create(['assigned_servant_id' => $other->id,   'service_group_id' => $group->id]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryList::class)
            ->set('filter', 'mine')
            ->assertViewHas('beneficiaries', fn ($b) => $b->contains('id', $mine->id))
            ->assertViewHas('beneficiaries', fn ($b) => ! $b->contains('id', $notMine->id));
    }
}
```

- [ ] **Step 2: Create VisitListLivewireTest**

```php
<?php

namespace Tests\Feature\Servant;

use App\Livewire\Servant\VisitList;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class VisitListLivewireTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    #[Test]
    public function servant_sees_their_visits(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $other   = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        $mine    = Visit::factory()->create(['created_by' => $servant->id, 'beneficiary_id' => $b->id, 'visit_date' => now()]);
        $notMine = Visit::factory()->create(['created_by' => $other->id,   'beneficiary_id' => $b->id, 'visit_date' => now()]);

        Livewire::actingAs($servant)
            ->test(VisitList::class)
            ->assertViewHas('visits', fn ($v) => $v->contains('id', $mine->id))
            ->assertViewHas('visits', fn ($v) => ! $v->contains('id', $notMine->id));
    }

    #[Test]
    public function filter_month_shows_current_month_visits(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        $thisMonth = Visit::factory()->create([
            'created_by' => $servant->id, 'beneficiary_id' => $b->id, 'visit_date' => now(),
        ]);
        $lastMonth = Visit::factory()->create([
            'created_by' => $servant->id, 'beneficiary_id' => $b->id, 'visit_date' => now()->subMonth(),
        ]);

        Livewire::actingAs($servant)
            ->test(VisitList::class)
            ->set('filter', 'month')
            ->assertViewHas('visits', fn ($v) => $v->contains('id', $thisMonth->id))
            ->assertViewHas('visits', fn ($v) => ! $v->contains('id', $lastMonth->id));
    }

    #[Test]
    public function filter_critical_shows_unresolved_critical_visits(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        $critical = Visit::factory()->create([
            'created_by' => $servant->id, 'beneficiary_id' => $b->id,
            'visit_date' => now(), 'is_critical' => true, 'critical_resolved_at' => null,
        ]);
        $normal = Visit::factory()->create([
            'created_by' => $servant->id, 'beneficiary_id' => $b->id,
            'visit_date' => now(), 'is_critical' => false,
        ]);

        Livewire::actingAs($servant)
            ->test(VisitList::class)
            ->set('filter', 'critical')
            ->assertViewHas('visits', fn ($v) => $v->contains('id', $critical->id))
            ->assertViewHas('visits', fn ($v) => ! $v->contains('id', $normal->id));
    }
}
```

- [ ] **Step 3: Run both tests**

```bash
cd E:/ministry-system && php artisan test tests/Feature/Servant/BeneficiaryListLivewireTest.php tests/Feature/Servant/VisitListLivewireTest.php --verbose 2>&1
```

Expected: 6 tests pass.

- [ ] **Step 4: Commit**

```bash
cd E:/ministry-system && git add tests/Feature/Servant/BeneficiaryListLivewireTest.php tests/Feature/Servant/VisitListLivewireTest.php && git commit -m "test: add BeneficiaryList and VisitList livewire tests"
```

---

### Task 6: CreateVisitWizardLivewireTest + BeneficiaryDetailLivewireTest

**Files:**
- Create: `tests/Feature/Servant/CreateVisitWizardLivewireTest.php`
- Create: `tests/Feature/Servant/BeneficiaryDetailLivewireTest.php`

- [ ] **Step 1: Create CreateVisitWizardLivewireTest**

```php
<?php

namespace Tests\Feature\Servant;

use App\Livewire\Servant\CreateVisitWizard;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class CreateVisitWizardLivewireTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    #[Test]
    public function step1_requires_beneficiary_selection(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->set('open', true)
            ->call('nextStep')
            ->assertHasErrors(['selectedBeneficiaryId' => 'required']);
    }

    #[Test]
    public function step2_requires_visit_type(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);

        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->set('open', true)
            ->set('selectedBeneficiaryId', $b->id)
            ->set('step', 2)
            ->call('nextStep')
            ->assertHasErrors(['visitType' => 'required']);
    }

    #[Test]
    public function submit_creates_visit_and_dispatches_event(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);

        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->set('selectedBeneficiaryId', $b->id)
            ->set('visitType', 'home_visit')
            ->set('beneficiaryStatus', 'good')
            ->set('durationMinutes', 60)
            ->call('submit')
            ->assertDispatched('visit-saved')
            ->assertDispatched('toast')
            ->assertSet('open', false);

        $this->assertDatabaseHas('visits', [
            'beneficiary_id'     => $b->id,
            'type'               => 'home_visit',
            'beneficiary_status' => 'good',
            'created_by'         => $servant->id,
        ]);
    }

    #[Test]
    public function servant_cannot_submit_visit_for_unowned_beneficiary(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $other   = Beneficiary::factory()->create(); // different group

        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->set('selectedBeneficiaryId', $other->id)
            ->set('visitType', 'home_visit')
            ->set('beneficiaryStatus', 'good')
            ->call('submit')
            ->assertForbidden();
    }

    #[Test]
    public function open_wizard_for_pre_selects_beneficiary_at_step2(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);

        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->dispatch('open-wizard-for', beneficiaryId: $b->id)
            ->assertSet('selectedBeneficiaryId', $b->id)
            ->assertSet('step', 2)
            ->assertSet('open', true);
    }
}
```

- [ ] **Step 2: Create BeneficiaryDetailLivewireTest**

```php
<?php

namespace Tests\Feature\Servant;

use App\Livewire\Servant\BeneficiaryDetail;
use App\Models\Beneficiary;
use App\Models\PrayerRequest;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class BeneficiaryDetailLivewireTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    #[Test]
    public function servant_can_view_their_beneficiary_detail(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id, 'service_group_id' => $group->id]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryDetail::class, ['beneficiary' => $b])
            ->assertOk();
    }

    #[Test]
    public function servant_cannot_view_beneficiary_from_different_group(): void
    {
        $group1  = ServiceGroup::factory()->create();
        $group2  = ServiceGroup::factory()->create();
        $servant = $this->createServant($group1);
        $other   = Beneficiary::factory()->create(['service_group_id' => $group2->id]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryDetail::class, ['beneficiary' => $other])
            ->assertForbidden();
    }

    #[Test]
    public function detail_shows_open_prayer_requests(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id, 'service_group_id' => $group->id]);

        $openPr   = PrayerRequest::factory()->create(['beneficiary_id' => $b->id, 'status' => 'open',   'created_by' => $servant->id]);
        $closedPr = PrayerRequest::factory()->create(['beneficiary_id' => $b->id, 'status' => 'closed', 'created_by' => $servant->id]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryDetail::class, ['beneficiary' => $b])
            ->assertViewHas('openPrayerRequests', fn ($prs) => $prs->contains('id', $openPr->id))
            ->assertViewHas('openPrayerRequests', fn ($prs) => ! $prs->contains('id', $closedPr->id));
    }

    #[Test]
    public function visit_this_dispatches_open_wizard_for(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id, 'service_group_id' => $group->id]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryDetail::class, ['beneficiary' => $b])
            ->call('visitThis')
            ->assertDispatched('open-wizard-for', beneficiaryId: $b->id);
    }
}
```

- [ ] **Step 3: Run both tests**

```bash
cd E:/ministry-system && php artisan test tests/Feature/Servant/CreateVisitWizardLivewireTest.php tests/Feature/Servant/BeneficiaryDetailLivewireTest.php --verbose 2>&1
```

Expected: 9 tests pass.

- [ ] **Step 4: Commit**

```bash
cd E:/ministry-system && git add tests/Feature/Servant/CreateVisitWizardLivewireTest.php tests/Feature/Servant/BeneficiaryDetailLivewireTest.php && git commit -m "test: add CreateVisitWizard and BeneficiaryDetail livewire tests"
```

---

## Phase 4: Notification Bell Verification

### Task 7: Channels Auth + NotificationsBell Test

**Files:**
- Verify: `routes/channels.php`
- Create: `tests/Feature/Servant/NotificationsBellLivewireTest.php`

- [ ] **Step 1: Verify channels.php is correct**

Read `routes/channels.php` and confirm it contains:

```php
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

If it uses `$user->id === $id` without casting, update it to cast both to `int` to prevent type-coercion bugs:

```php
return (int) $user->id === (int) $id;
```

- [ ] **Step 2: Write notification bell tests**

Create `tests/Feature/Servant/NotificationsBellLivewireTest.php`:

```php
<?php

namespace Tests\Feature\Servant;

use App\Livewire\Servant\NotificationsBell;
use App\Models\MinistryNotification;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class NotificationsBellLivewireTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    #[Test]
    public function notification_bell_shows_own_notifications(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $other   = $this->createServant($group);

        $mine    = MinistryNotification::factory()->create(['user_id' => $servant->id, 'read_at' => null]);
        $notMine = MinistryNotification::factory()->create(['user_id' => $other->id,   'read_at' => null]);

        Livewire::actingAs($servant)
            ->test(NotificationsBell::class)
            ->call('loadNotifications')
            ->assertViewHas('notifications', fn ($n) => $n->contains('id', $mine->id))
            ->assertViewHas('notifications', fn ($n) => ! $n->contains('id', $notMine->id));
    }

    #[Test]
    public function mark_read_updates_single_notification(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $notif = MinistryNotification::factory()->create(['user_id' => $servant->id, 'read_at' => null]);

        Livewire::actingAs($servant)
            ->test(NotificationsBell::class)
            ->call('markRead', $notif->id);

        $this->assertNotNull($notif->fresh()->read_at);
    }

    #[Test]
    public function mark_all_read_marks_all_own_notifications(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        MinistryNotification::factory()->count(3)->create(['user_id' => $servant->id, 'read_at' => null]);

        Livewire::actingAs($servant)
            ->test(NotificationsBell::class)
            ->call('markAllRead');

        $this->assertEquals(0,
            MinistryNotification::where('user_id', $servant->id)->whereNull('read_at')->count()
        );
    }

    #[Test]
    public function servant_cannot_mark_another_users_notification_as_read(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $other   = $this->createServant($group);

        $notif = MinistryNotification::factory()->create(['user_id' => $other->id, 'read_at' => null]);

        Livewire::actingAs($servant)
            ->test(NotificationsBell::class)
            ->call('markRead', $notif->id);

        $this->assertNull($notif->fresh()->read_at, 'Notification should remain unread');
    }
}
```

- [ ] **Step 3: Check if MinistryNotification has a factory**

```bash
ls E:/ministry-system/database/factories/MinistryNotificationFactory.php 2>&1
```

If missing, create it:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MinistryNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'   => User::factory(),
            'title'     => $this->faker->sentence(4),
            'body'      => $this->faker->sentence(),
            'type'      => 'general',
            'read_at'   => null,
            'metadata'  => [],
        ];
    }
}
```

- [ ] **Step 4: Run the test**

```bash
cd E:/ministry-system && php artisan test tests/Feature/Servant/NotificationsBellLivewireTest.php --verbose 2>&1
```

Expected: 4 tests pass. If `markRead` doesn't check ownership, fix `NotificationsBell::markRead()` to add `->where('user_id', auth()->id())` to the query.

- [ ] **Step 5: Fix ownership guard in NotificationsBell if needed**

In `app/Livewire/Servant/NotificationsBell.php`, ensure `markRead` scopes to the current user:

```php
public function markRead(int $id): void
{
    MinistryNotification::where('id', $id)
        ->where('user_id', auth()->id())  // ownership guard
        ->update(['read_at' => now()]);
    // ... rest of method
}
```

- [ ] **Step 6: Commit**

```bash
cd E:/ministry-system && git add routes/channels.php app/Livewire/Servant/NotificationsBell.php tests/Feature/Servant/NotificationsBellLivewireTest.php database/factories/MinistryNotificationFactory.php && git commit -m "fix: add ownership guard to markRead + add notification bell tests"
```

---

## Phase 5: Run Full Test Suite

### Task 8: Full Test Run + Coverage Report

- [ ] **Step 1: Run all servant panel tests**

```bash
cd E:/ministry-system && php artisan test tests/Feature/Servant/ --verbose 2>&1
```

Expected: All tests in `tests/Feature/Servant/` pass.

- [ ] **Step 2: Run full project test suite**

```bash
cd E:/ministry-system && php artisan test --stop-on-failure 2>&1 | tail -30
```

Expected: All existing tests still pass (no regressions).

- [ ] **Step 3: Check factories exist for all models used in tests**

Run:
```bash
cd E:/ministry-system && ls database/factories/ 2>&1
```

Verify: `BeneficiaryFactory.php`, `VisitFactory.php`, `ScheduledVisitFactory.php`, `PrayerRequestFactory.php`, `MinistryNotificationFactory.php`, `ServiceGroupFactory.php`, `UserFactory.php` all exist.

- [ ] **Step 4: Final commit**

```bash
cd E:/ministry-system && git add -A && git commit -m "test: complete servant panel test suite — Phase 3-5"
```

---

## Quick Reference

### New Routes Added

```
GET  /servant/scheduled-visits    servant.scheduled-visits
GET  /servant/prayer-requests     servant.prayer-requests
```

### New Livewire Components

```
App\Livewire\Servant\ScheduledVisitList   → servant.scheduled-visit-list blade
App\Livewire\Servant\PrayerRequestList    → servant.prayer-request-list blade
```

### New Tests

```
tests/Feature/Servant/ServantPanelAccessTest.php         (7 tests)
tests/Feature/Servant/DashboardLivewireTest.php          (4 tests)
tests/Feature/Servant/BeneficiaryListLivewireTest.php    (3 tests)
tests/Feature/Servant/VisitListLivewireTest.php          (3 tests)
tests/Feature/Servant/CreateVisitWizardLivewireTest.php  (5 tests)
tests/Feature/Servant/BeneficiaryDetailLivewireTest.php  (4 tests)
tests/Feature/Servant/ScheduledVisitListLivewireTest.php (4 tests)
tests/Feature/Servant/PrayerRequestListLivewireTest.php  (5 tests)
tests/Feature/Servant/NotificationsBellLivewireTest.php  (4 tests)
                                                         ─────────
                                                   Total: 39 tests
```

### New Factories

```
database/factories/ScheduledVisitFactory.php
database/factories/PrayerRequestFactory.php
database/factories/MinistryNotificationFactory.php
```
