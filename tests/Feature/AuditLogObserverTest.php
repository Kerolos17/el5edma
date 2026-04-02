<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogObserverTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($this->admin);
    }

    #[Test]
    public function creating_a_beneficiary_logs_a_created_audit_entry(): void
    {
        $beneficiary = Beneficiary::factory()->create();

        $this->assertDatabaseHas('audit_logs', [
            'user_id'    => $this->admin->id,
            'model_type' => Beneficiary::class,
            'model_id'   => $beneficiary->id,
            'action'     => 'created',
        ]);
    }

    #[Test]
    public function updating_a_beneficiary_logs_an_updated_audit_entry(): void
    {
        $beneficiary = Beneficiary::factory()->create(['full_name' => 'Original Name']);

        AuditLog::query()->delete(); // clear creation log

        $beneficiary->update(['full_name' => 'Updated Name']);

        $log = AuditLog::where([
            'model_type' => Beneficiary::class,
            'model_id'   => $beneficiary->id,
            'action'     => 'updated',
        ])->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('full_name', $log->new_values);
        $this->assertEquals('Updated Name', $log->new_values['full_name']);
    }

    #[Test]
    public function deleting_a_beneficiary_logs_a_deleted_audit_entry(): void
    {
        $beneficiary = Beneficiary::factory()->create();
        $id          = $beneficiary->id;

        $beneficiary->delete();

        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Beneficiary::class,
            'model_id'   => $id,
            'action'     => 'deleted',
        ]);
    }

    #[Test]
    public function creating_a_visit_logs_a_created_audit_entry(): void
    {
        $beneficiary = Beneficiary::factory()->create();
        $visit       = Visit::factory()->create(['beneficiary_id' => $beneficiary->id]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'    => $this->admin->id,
            'model_type' => Visit::class,
            'model_id'   => $visit->id,
            'action'     => 'created',
        ]);
    }

    #[Test]
    public function audit_log_only_records_dirty_fields_on_update(): void
    {
        $beneficiary = Beneficiary::factory()->create([
            'full_name' => 'Test Name',
            'area'      => 'Test Area',
        ]);

        AuditLog::query()->delete();

        $beneficiary->update(['full_name' => 'New Name']);

        $log = AuditLog::where([
            'model_type' => Beneficiary::class,
            'model_id'   => $beneficiary->id,
            'action'     => 'updated',
        ])->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('full_name', $log->new_values);
        $this->assertArrayNotHasKey('area', $log->new_values);
    }

    #[Test]
    public function audit_log_is_not_created_when_no_user_is_authenticated(): void
    {
        auth()->logout();

        $count = AuditLog::count();
        Beneficiary::factory()->create();

        $this->assertEquals($count, AuditLog::count());
    }
}
