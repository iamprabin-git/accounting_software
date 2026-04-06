<?php

namespace Tests\Feature;

use App\Models\AccountingAuditLog;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardFinancialRatiosTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_dashboard_includes_financial_ratios_payload(): void
    {
        $user = User::factory()->companyOwner()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('financialRatios', fn (AssertableInertia $ratios) => $ratios
                ->where('can_open_reports', true)
                ->where('admin_company_id', null)
                ->has('as_of')
                ->has('pl_from')
                ->has('pl_to')
                ->has('report_route_params')
                ->has('items')
            ));
    }

    public function test_admin_dashboard_includes_financial_ratios_when_company_exists(): void
    {
        $user = User::factory()->admin()->create();
        User::factory()->companyOwner()->create();

        $expectedCompanyId = (int) Company::query()->orderBy('id')->value('id');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('financialRatios.can_open_reports', true)
            ->where('financialRatios.admin_company_id', $expectedCompanyId));
    }

    public function test_dashboard_includes_approval_sla_payload_with_oldest_pending(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'transaction_date' => now()->toDateString(),
            'status' => JournalEntry::STATUS_PENDING,
            'submitted_at' => now()->subDays(8),
        ]);
        JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'transaction_date' => now()->toDateString(),
            'status' => JournalEntry::STATUS_PENDING,
            'submitted_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($owner)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('approvalSla.pending_total', 2)
            ->where('approvalSla.over_2_days', 2)
            ->where('approvalSla.over_7_days', 1)
            ->where('approvalSla.admin_company_id', null)
            ->where('approvalSla.oldest_pending.pending_age_days', 8));
    }

    public function test_dashboard_includes_audit_integrity_trend_ordered_oldest_first(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $t0 = now()->subDays(3);
        AccountingAuditLog::query()->create([
            'company_id' => $company->id,
            'action' => 'audit.integrity_nightly_ok',
            'metadata' => null,
            'created_at' => $t0,
        ]);
        AccountingAuditLog::query()->create([
            'company_id' => $company->id,
            'action' => 'audit.integrity_nightly_failed',
            'metadata' => ['valid' => false],
            'created_at' => $t0->copy()->addDay(),
        ]);
        AccountingAuditLog::query()->create([
            'company_id' => $company->id,
            'action' => 'audit.integrity_nightly_ok',
            'metadata' => null,
            'created_at' => $t0->copy()->addDays(2),
        ]);

        $response = $this->actingAs($owner)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('auditIntegrityTrend', fn (AssertableInertia $trend) => $trend
                ->where('admin_company_id', null)
                ->has('points', 3)
                ->where('points.0.pass', true)
                ->where('points.1.pass', false)
                ->where('points.2.pass', true)));
    }
}
