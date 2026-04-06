<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyIntegrationsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_view_integrations(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->get(route('company.integrations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Company/Integrations/Index')
                ->where('integrationContext.is_platform_admin', false)
                ->where('integrationContext.company.id', $company->id));
    }

    public function test_platform_admin_can_view_integrations(): void
    {
        Company::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('company.integrations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Company/Integrations/Index')
                ->where('integrationContext.is_platform_admin', true));
    }

    public function test_staff_cannot_view_integrations(): void
    {
        $company = Company::factory()->create();
        $staff = User::factory()->staff($company)->create();

        $this->actingAs($staff)
            ->get(route('company.integrations.index'))
            ->assertForbidden();
    }
}
