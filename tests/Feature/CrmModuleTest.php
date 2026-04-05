<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CrmAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_plan_cannot_access_crm(): void
    {
        $company = Company::factory()->starter()->create();
        $user = User::factory()->companyOwner($company)->create();

        $this->actingAs($user)
            ->get(route('crm.dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_professional_plan_can_access_crm_dashboard(): void
    {
        $company = Company::factory()->professional()->create();
        $user = User::factory()->companyOwner($company)->create();

        $this->actingAs($user)
            ->get(route('crm.dashboard'))
            ->assertOk();
    }

    public function test_company_can_create_crm_account(): void
    {
        $company = Company::factory()->professional()->create();
        $user = User::factory()->companyOwner($company)->create();

        $this->actingAs($user)
            ->post(route('crm.accounts.store'), [
                'name' => 'Acme Corp',
                'industry' => 'Software',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('crm_accounts', [
            'company_id' => $company->id,
            'name' => 'Acme Corp',
        ]);
    }

    public function test_crm_account_scoped_to_company(): void
    {
        $a = Company::factory()->professional()->create();
        $b = Company::factory()->professional()->create();
        $account = CrmAccount::query()->create([
            'company_id' => $a->id,
            'name' => 'Secret',
        ]);
        $user = User::factory()->companyOwner($b)->create();

        $this->actingAs($user)
            ->get(route('crm.accounts.edit', ['account' => $account->id]))
            ->assertForbidden();
    }
}
