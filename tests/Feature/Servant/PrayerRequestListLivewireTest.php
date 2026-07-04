<?php

declare(strict_types=1);

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
    use CreatesTestUsers, RefreshDatabase;

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
