<?php

namespace Tests\Feature;

use App\Models\Company;
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
}
