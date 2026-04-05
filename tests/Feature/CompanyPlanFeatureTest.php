<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPlanFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_plan_blocks_inventory(): void
    {
        $company = Company::factory()->starter()->create();
        $user = User::factory()->companyOwner($company)->create();

        $this->actingAs($user)
            ->get(route('inventory.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_professional_plan_allows_inventory(): void
    {
        $company = Company::factory()->professional()->create();
        $user = User::factory()->companyOwner($company)->create();

        $this->actingAs($user)
            ->get(route('inventory.index'))
            ->assertOk();
    }

    public function test_professional_plan_blocks_members(): void
    {
        $company = Company::factory()->professional()->create();
        $user = User::factory()->companyOwner($company)->create();

        $this->actingAs($user)
            ->get(route('members.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_enterprise_with_members_disabled_blocks_member_routes(): void
    {
        $company = Company::factory()->create([
            'plan' => Company::PLAN_ENTERPRISE,
            'feature_members_enabled' => false,
            'feature_inventory_enabled' => true,
        ]);
        $user = User::factory()->companyOwner($company)->create();

        $this->actingAs($user)
            ->get(route('members.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_enterprise_with_inventory_disabled_blocks_inventory(): void
    {
        $company = Company::factory()->create([
            'plan' => Company::PLAN_ENTERPRISE,
            'feature_members_enabled' => true,
            'feature_inventory_enabled' => false,
        ]);
        $user = User::factory()->companyOwner($company)->create();

        $this->actingAs($user)
            ->get(route('inventory.index'))
            ->assertRedirect(route('dashboard'));
    }
}
