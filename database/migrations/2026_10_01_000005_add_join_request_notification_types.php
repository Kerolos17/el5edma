<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TYPES = [
        'birthday',
        'critical_case',
        'visit_reminder',
        'unvisited_alert',
        'new_beneficiary',
        'servant_registered',
        'visit_created',
        'join_request_submitted',
        'join_request_decision',
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $values = collect(self::TYPES)->map(fn (string $type): string => "'{$type}'")->implode(',');
            DB::statement("ALTER TABLE ministry_notifications MODIFY COLUMN type ENUM({$values}) NOT NULL");

            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteTable(self::TYPES);
        }
    }

    public function down(): void
    {
        $count = DB::table('ministry_notifications')
            ->whereIn('type', ['join_request_submitted', 'join_request_decision'])
            ->count();

        if ($count > 0) {
            throw new RuntimeException("Cannot rollback: {$count} join-request notifications exist.");
        }

        $types = array_values(array_filter(self::TYPES, fn (string $type): bool => ! str_starts_with($type, 'join_request')));

        if (DB::getDriverName() === 'mysql') {
            $values = collect($types)->map(fn (string $type): string => "'{$type}'")->implode(',');
            DB::statement("ALTER TABLE ministry_notifications MODIFY COLUMN type ENUM({$values}) NOT NULL");

            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteTable($types);
        }
    }

    private function rebuildSqliteTable(array $types): void
    {
        $values = collect($types)->map(fn (string $type): string => '"' . $type . '"')->implode(',');

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement("CREATE TABLE ministry_notifications_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            user_id INTEGER NOT NULL,
            type TEXT NOT NULL CHECK(type IN ({$values})),
            title VARCHAR NOT NULL,
            body TEXT NOT NULL,
            data TEXT,
            dedupe_key VARCHAR(128),
            read_at DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        DB::statement('INSERT INTO ministry_notifications_new
            (id, user_id, type, title, body, data, dedupe_key, read_at, created_at)
            SELECT id, user_id, type, title, body, data, dedupe_key, read_at, created_at
            FROM ministry_notifications');
        DB::statement('DROP TABLE ministry_notifications');
        DB::statement('ALTER TABLE ministry_notifications_new RENAME TO ministry_notifications');
        DB::statement('CREATE UNIQUE INDEX ministry_notifications_dedupe_key_unique ON ministry_notifications(dedupe_key)');
        DB::statement('CREATE INDEX ministry_notifications_user_id_read_at_index ON ministry_notifications(user_id, read_at)');
        DB::statement('CREATE INDEX idx_notifications_user_read ON ministry_notifications(user_id, read_at)');
        DB::statement('CREATE INDEX idx_notifications_created ON ministry_notifications(created_at)');
        DB::statement('CREATE INDEX mn_user_created_idx ON ministry_notifications(user_id, created_at)');
        DB::statement('CREATE INDEX mn_user_unread_idx ON ministry_notifications(user_id, read_at) WHERE read_at IS NULL');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
