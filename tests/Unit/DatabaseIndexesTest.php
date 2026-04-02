<?php

// Feature: notifications-optimization — Unit tests for database index existence (Requirements 7.1, 7.2, 7.3)

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseIndexesTest extends TestCase
{
    use RefreshDatabase;

    private function getIndexNames(string $table): array
    {
        return collect(Schema::getIndexes($table))
            ->pluck('name')
            ->all();
    }

    // Requirements: 7.1 — فهرس (user_id, created_at) على ministry_notifications

    public function test_mn_user_created_idx_exists_on_ministry_notifications(): void
    {
        $this->assertContains(
            'mn_user_created_idx',
            $this->getIndexNames('ministry_notifications'),
            'الفهرس mn_user_created_idx غير موجود في جدول ministry_notifications'
        );
    }

    // Requirements: 7.2 — فهرس (user_id, read_at, created_at) على ministry_notifications

    public function test_mn_user_unread_idx_exists_on_ministry_notifications(): void
    {
        $this->assertContains(
            'mn_user_unread_idx',
            $this->getIndexNames('ministry_notifications'),
            'الفهرس mn_user_unread_idx غير موجود في جدول ministry_notifications'
        );
    }

    // Requirements: 7.3 — فهرس fcm_token على users

    public function test_users_fcm_token_idx_exists_on_users(): void
    {
        $this->assertContains(
            'users_fcm_token_idx',
            $this->getIndexNames('users'),
            'الفهرس users_fcm_token_idx غير موجود في جدول users'
        );
    }
}
