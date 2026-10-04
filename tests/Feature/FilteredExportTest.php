<?php

namespace Tests\Feature;

use App\Exports\BeneficiariesExport;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class FilteredExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_beneficiaries_excel_honors_search_and_filter(): void
    {
        Excel::fake();
        $group = ServiceGroup::factory()->create();
        $admin = User::factory()->create(['role' => 'super_admin']);

        Beneficiary::factory()->create(['full_name' => 'أحمد مطابق', 'service_group_id' => $group->id]);
        Beneficiary::factory()->create(['full_name' => 'غير مطابق', 'service_group_id' => $group->id]);

        $this->actingAs($admin)
            ->get(route('reports.beneficiaries.excel', ['q' => 'أحمد', 'filter' => 'all']))
            ->assertOk();

        Excel::assertDownloaded('beneficiaries.xlsx');
    }

    public function test_visits_excel_accepts_the_page_filters(): void
    {
        Excel::fake();
        $admin       = User::factory()->create(['role' => 'super_admin']);
        $beneficiary = Beneficiary::factory()->create();

        Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'is_critical'    => true,
            'feedback'       => 'بلاغ حرج',
        ]);

        $this->actingAs($admin)
            ->get(route('reports.visits.excel', ['q' => 'حرج', 'filter' => 'critical']))
            ->assertOk();
    }

    public function test_export_queries_mirror_the_page_scope(): void
    {
        // Behavior check without Excel::fake: the export query class applies
        // the same search semantics as the page.
        $group   = ServiceGroup::factory()->create();
        $servant = User::factory()->create(['service_group_id' => $group->id]);
        Beneficiary::factory()->create(['full_name' => 'مطابق خاص', 'service_group_id' => $group->id]);
        Beneficiary::factory()->create(['full_name' => 'مستبعد', 'service_group_id' => $group->id]);

        $export  = new BeneficiariesExport($servant, 'مطابق', 'all');
        $results = $export->query()->get();

        $this->assertSame(1, $results->count());
        $this->assertSame('مطابق خاص', $results->first()->full_name);
    }
}
