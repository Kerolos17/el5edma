<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MaintenancePingTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_404_when_no_token_is_configured(): void
    {
        Config::set('app.maintenance_token', '');

        $this->post('/maintenance/ping', [], ['X-Maintenance-Token' => 'anything'])
            ->assertNotFound();
    }

    public function test_returns_404_for_a_wrong_token(): void
    {
        Config::set('app.maintenance_token', 'correct-horse');

        $this->post('/maintenance/ping', [], ['X-Maintenance-Token' => 'wrong'])
            ->assertNotFound();
    }

    public function test_runs_scheduler_and_queue_with_a_valid_token(): void
    {
        Config::set('app.maintenance_token', 'correct-horse');

        $response = $this->post('/maintenance/ping', [], ['X-Maintenance-Token' => 'correct-horse'])
            ->assertOk()
            ->assertJsonStructure(['queue', 'schedule']);

        // Both run for real. The scheduler has nothing due in this window and
        // echoes that; queue:work is silent when it exits on an empty queue in
        // a non-interactive shell, so only its presence is asserted there.
        $this->assertStringContainsStringIgnoringCase('no scheduled commands', $response->json('schedule'));
    }
}
