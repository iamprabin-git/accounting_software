<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreBankingOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_hub_ok_for_enterprise_owner(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->get(route('banking.operations'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Banking/OperationsHub')
                ->has('stats'));
    }

    public function test_operations_hub_forbidden_when_members_disabled(): void
    {
        $company = Company::factory()->create([
            'plan' => Company::PLAN_ENTERPRISE,
            'feature_members_enabled' => false,
        ]);
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->get(route('banking.operations'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_operations_hub_forbidden_on_starter_plan(): void
    {
        $company = Company::factory()->starter()->create([
            'feature_members_enabled' => true,
        ]);
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->get(route('banking.operations'))
            ->assertRedirect(route('dashboard'));
    }
}
