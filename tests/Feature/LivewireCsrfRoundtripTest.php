<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

/**
 * Reproduces the exact browser cycle: real HTTP GET (cookie jar + session),
 * then a genuine POST /livewire/update carrying _token + a real snapshot,
 * exactly like Livewire's JS does. Catches CSRF/session stack breakage
 * that Livewire::test() never exercises.
 */
class LivewireCsrfRoundtripTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    #[Test]
    public function livewire_update_roundtrip_passes_csrf(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $page = $this->actingAs($servant)->get('/app/dashboard');
        $page->assertOk();
        $html = $page->getContent();

        $this->assertNotFalse($html);

        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $tokenMatch);
        $this->assertNotEmpty($tokenMatch, 'csrf meta tag missing');
        $token = html_entity_decode($tokenMatch[1], ENT_QUOTES);

        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $snapMatches);
        $this->assertNotEmpty($snapMatches[1], 'no livewire snapshot on page');
        // Livewire JS sends the snapshot back as a JSON-encoded STRING, not an object.
        $snapshotJson = null;
        foreach ($snapMatches[1] as $raw) {
            $decoded = json_decode(html_entity_decode($raw, ENT_QUOTES), true);
            if (($decoded['memo']['name'] ?? null) === 'servant.create-visit-wizard') {
                $snapshotJson = html_entity_decode($raw, ENT_QUOTES);

                break;
            }
        }
        $this->assertNotEmpty($snapshotJson, 'wizard snapshot not found on page');

        $response = $this->postJson('/livewire/update', [
            '_token'     => $token,
            'components' => [[
                'snapshot' => $snapshotJson,
                'updates'  => ['open' => true],
                'calls'    => [],
            ]],
        ]);

        if ($response->status() !== 200) {
            fwrite(STDERR, "\nSTATUS: " . $response->status() . "\n");
            fwrite(STDERR, substr($response->getContent(), 0, 600) . "\n");
        }

        $response->assertOk();
    }
}
