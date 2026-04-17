<?php

namespace Tests\Unit\Models;

use App\Models\MinistryNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MinistryNotificationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relationship(): void
    {
        $user         = User::factory()->create();
        $notification = MinistryNotification::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $notification->user);
        $this->assertSame($user->id, $notification->user->id);
    }

    public function test_no_updated_at_timestamp(): void
    {
        $notification = MinistryNotification::factory()->create();

        $this->assertFalse($notification->timestamps);
        $this->assertArrayNotHasKey('updated_at', $notification->getAttributes());
    }

    public function test_read_at_is_cast_to_carbon_when_set(): void
    {
        $notification = MinistryNotification::factory()->create(['read_at' => now()]);
        $notification->refresh();

        $this->assertInstanceOf(Carbon::class, $notification->read_at);
    }

    public function test_read_at_is_null_by_default(): void
    {
        $notification = MinistryNotification::factory()->create(['read_at' => null]);

        $this->assertNull($notification->read_at);
    }

    public function test_data_is_cast_to_array(): void
    {
        $notification = MinistryNotification::factory()->create([
            'data' => ['url' => '/admin/beneficiaries/1'],
        ]);
        $notification->refresh();

        $this->assertIsArray($notification->data);
        $this->assertSame('/admin/beneficiaries/1', $notification->data['url']);
    }

    public function test_factory_unread_state(): void
    {
        $notification = MinistryNotification::factory()->unread()->create();

        $this->assertNull($notification->read_at);
    }

    public function test_factory_read_state(): void
    {
        $notification = MinistryNotification::factory()->read()->create();

        $this->assertNotNull($notification->read_at);
    }
}
