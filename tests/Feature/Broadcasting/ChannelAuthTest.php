<?php

declare(strict_types=1);

namespace Tests\Feature\Broadcasting;

use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ChannelAuthTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    #[Test]
    public function user_can_authenticate_to_their_own_channel(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $this->actingAs($servant)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-user.{$servant->id}",
                'socket_id'    => '123.456',
            ])
            ->assertOk();
    }

    #[Test]
    public function user_cannot_authenticate_to_another_users_channel(): void
    {
        $group    = ServiceGroup::factory()->create();
        $servant1 = $this->createServant($group);
        $servant2 = $this->createServant($group);

        $this->actingAs($servant1)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-user.{$servant2->id}",
                'socket_id'    => '123.456',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function unauthenticated_user_cannot_authenticate_to_any_channel(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $this->post('/broadcasting/auth', [
            'channel_name' => "private-user.{$servant->id}",
            'socket_id'    => '123.456',
        ])
        ->assertUnauthorized();
    }
}
