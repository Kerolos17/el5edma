<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendFcmNotificationJob;
use App\Models\User;
use App\Services\InternalNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PerRecipientPushTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_uses_each_recipients_own_localized_text(): void
    {
        Bus::fake([SendFcmNotificationJob::class]);

        $ar = User::factory()->create(['role' => UserRole::Servant, 'is_active' => true, 'locale' => 'ar', 'fcm_token' => 'token-ar']);
        $en = User::factory()->create(['role' => UserRole::Servant, 'is_active' => true, 'locale' => 'en', 'fcm_token' => 'token-en']);

        app(InternalNotificationService::class)->notifyUsers(
            collect([$ar, $en]),
            'new_beneficiary',
            'creator-title',
            'creator-body',
            [],
            fn (User $u) => $u->locale === 'ar' ? ['عنوان', 'نص'] : ['Title', 'Body'],
        );

        Bus::assertDispatchedSync(SendFcmNotificationJob::class, fn ($job) => $job->tokens === ['token-ar'] && $job->title === 'عنوان' && $job->body === 'نص');
        Bus::assertDispatchedSync(SendFcmNotificationJob::class, fn ($job) => $job->tokens === ['token-en'] && $job->title === 'Title' && $job->body === 'Body');
    }
}
