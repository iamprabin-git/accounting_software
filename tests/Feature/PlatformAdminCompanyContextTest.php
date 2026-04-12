<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAdminCompanyContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_set_accounting_company_context(): void
    {
        $a = Company::factory()->create(['name' => 'Alpha Co']);
        $b = Company::factory()->create(['name' => 'Beta Co']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('platform.company-context.update', absolute: false), [
                'company_id' => $b->id,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame($b->id, session('accounting_company_id'));
    }

    public function test_non_admin_cannot_set_platform_company_context(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->post(route('platform.company-context.update', absolute: false), [
                'company_id' => $company->id,
            ])
            ->assertForbidden();
    }
}
