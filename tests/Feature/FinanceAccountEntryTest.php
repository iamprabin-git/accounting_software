<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FinancialPosition;
use App\Models\FinancialPositionMovement;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceAccountEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_entry_page_resolves_loan_or_savings_by_account_number(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        $member = Member::query()->create([
            'company_id' => $company->id,
            'reference_code' => 'M1',
            'name' => 'Member',
            'status' => Member::STATUS_APPROVED,
            'created_by_user_id' => $owner->id,
            'approved_by_user_id' => $owner->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $position = FinancialPosition::query()->create([
            'company_id' => $company->id,
            'member_id' => $member->id,
            'account_number' => 'SV-777',
            'category' => FinancialPosition::CATEGORY_SAVINGS,
            'title' => 'Saver',
            'principal_cents' => 5_000,
            'annual_interest_rate_percent' => '3',
            'start_date' => null,
            'notes' => null,
        ]);

        FinancialPositionMovement::query()->create([
            'financial_position_id' => $position->id,
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'type' => FinancialPositionMovement::TYPE_OPENING,
            'amount_cents' => 5_000,
            'balance_after_cents' => 5_000,
            'memo' => 'Opening balance',
        ]);

        $this->actingAs($owner)
            ->get(route('finance.account-entry', [
                'category' => 'savings',
                'account_number' => 'SV-777',
            ], absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounting/Finance/AccountEntry')
                ->where('resolved.id', $position->id)
                ->where('resolved.account_number', 'SV-777')
                ->where('resolved.principal_cents', 5_000));
    }
}
