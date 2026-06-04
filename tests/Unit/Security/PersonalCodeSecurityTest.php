<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * Security regression tests for personal code storage.
 *
 * Locks in three hardening guarantees against the previous insecure design:
 *  1. The personal code is encrypted at rest (never readable from a raw DB dump).
 *  2. The lookup hash is a peppered HMAC, not an unsalted SHA-256.
 *  3. The plaintext code never leaks through model serialization.
 */
class PersonalCodeSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_code_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create(['personal_code' => '1234']);
        $user->refresh();

        $stored = $user->getRawOriginal('personal_code');

        // Raw stored value must not be the plaintext code.
        $this->assertNotSame('1234', $stored);
        // And it must decrypt back to the original code.
        $this->assertSame('1234', Crypt::decryptString($stored));
        // The accessor transparently returns the decrypted value.
        $this->assertSame('1234', $user->personal_code);
    }

    public function test_lookup_hash_is_peppered_hmac_not_plain_sha256(): void
    {
        $user = User::factory()->create(['personal_code' => '4321']);
        $user->refresh();

        $this->assertSame(User::hashPersonalCode('4321'), $user->personal_code_hash);
        // The peppered HMAC must differ from the legacy unsalted SHA-256.
        $this->assertNotSame(hash('sha256', '4321'), $user->personal_code_hash);
    }

    public function test_hash_helper_depends_on_app_key_pepper(): void
    {
        $expected = hash_hmac('sha256', '5678', (string) config('app.key'));
        $this->assertSame($expected, User::hashPersonalCode('5678'));
    }

    public function test_personal_code_is_hidden_from_serialization(): void
    {
        $user = User::factory()->create(['personal_code' => '9876']);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('personal_code', $array);
        $this->assertArrayNotHasKey('personal_code_hash', $array);
        $this->assertStringNotContainsString('9876', json_encode($array));
    }

    public function test_lookup_by_hash_still_resolves_user(): void
    {
        $user = User::factory()->create(['personal_code' => '2468', 'is_active' => true]);

        $found = User::where('personal_code_hash', User::hashPersonalCode('2468'))->first();

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function test_null_personal_code_clears_hash(): void
    {
        $user                = User::factory()->create(['personal_code' => '1357']);
        $user->personal_code = null;
        $user->save();
        $user->refresh();

        $this->assertNull($user->personal_code);
        $this->assertNull($user->personal_code_hash);
    }
}
