<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FinancialPosition;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtorsCreditorsFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_manage_debtors_creditors_and_finance(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->get(route('debtors.index', absolute: false))
            ->assertOk();

        $this->actingAs($owner)
            ->post(route('debtors.store', absolute: false), [
                'name' => 'Acme Buyer',
                'reference' => 'INV-1',
                'balance' => '150.50',
                'notes' => null,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('debtors', [
            'company_id' => $company->id,
            'name' => 'Acme Buyer',
            'balance_cents' => 15_050,
        ]);

        $this->actingAs($owner)
            ->get(route('creditors.index', absolute: false))
            ->assertOk();

        $this->actingAs($owner)
            ->post(route('creditors.store', absolute: false), [
                'name' => 'Supplier Co',
                'balance' => '200',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('creditors', [
            'company_id' => $company->id,
            'balance_cents' => 20_000,
        ]);

        $this->actingAs($owner)
            ->get(route('finance.positions.index', ['category' => 'savings'], absolute: false))
            ->assertOk();

        $member = Member::query()->create([
            'company_id' => $company->id,
            'reference_code' => 'MEM-1',
            'name' => 'Saver',
            'email' => null,
            'phone' => null,
            'address' => null,
            'notes' => null,
            'status' => Member::STATUS_APPROVED,
            'created_by_user_id' => $owner->id,
            'approved_by_user_id' => $owner->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->actingAs($owner)
            ->post(route('finance.positions.store', ['category' => 'savings'], absolute: false), [
                'title' => 'High-yield account',
                'principal' => '10000',
                'annual_interest_rate_percent' => '4.5',
                'start_date' => null,
                'notes' => null,
                'member_id' => $member->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('financial_positions', [
            'company_id' => $company->id,
            'category' => FinancialPosition::CATEGORY_SAVINGS,
            'title' => 'High-yield account',
            'principal_cents' => 1_000_000,
        ]);

        $position = FinancialPosition::query()->first();
        $this->assertNotNull($position);
        $this->assertSame(45_000, $position->annualInterestCents());
    }

    public function test_staff_can_access_debtor_routes(): void
    {
        $company = Company::factory()->create();
        $staff = User::factory()->staff($company)->create();

        $this->actingAs($staff)
            ->get(route('debtors.index', absolute: false))
            ->assertOk();
    }

    public function test_end_user_cannot_access_debtor_routes(): void
    {
        $company = Company::factory()->create();
        $endUser = User::factory()->endUser($company)->create();

        $this->actingAs($endUser)
            ->get(route('debtors.index', absolute: false))
            ->assertForbidden();
    }
}
