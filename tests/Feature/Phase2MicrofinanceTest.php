<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\FinancialPosition;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2MicrofinanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: ChartAccount, 1: ChartAccount}
     */
    private function cashAndReceivable(Company $company, User $owner): array
    {
        $cash = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1100',
            'name' => 'Cash',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $receivable = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '11-0001',
            'name' => 'Loans receivable',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        return [$cash, $receivable];
    }

    private function approvedMember(Company $company, User $actor): Member
    {
        return Member::query()->create([
            'company_id' => $company->id,
            'reference_code' => 'M-'.uniqid(),
            'name' => 'Approved member',
            'email' => null,
            'phone' => null,
            'address' => null,
            'notes' => null,
            'status' => Member::STATUS_APPROVED,
            'created_by_user_id' => $actor->id,
            'approved_by_user_id' => $actor->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    public function test_teller_day_close_rejects_duplicate_date_per_user(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)->post(route('teller.day-close.store'), [
            'close_date' => '2026-04-10',
            'opening_cash' => '100',
            'counted_cash' => '98.50',
            'expected_cash' => '99',
            'memo' => 'Evening close',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('teller_day_closes', [
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'opening_cash_cents' => 10000,
            'counted_cash_cents' => 9850,
            'expected_cash_cents' => 9900,
        ]);

        $this->actingAs($owner)->post(route('teller.day-close.store'), [
            'close_date' => '2026-04-10',
            'opening_cash' => '0',
            'counted_cash' => '1',
        ])->assertSessionHasErrors('close_date');
    }

    public function test_par_aging_report_loads_for_outstanding_loan(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        [$cash, $receivable] = $this->cashAndReceivable($company, $owner);
        $member = $this->approvedMember($company, $owner);

        $product = LoanProduct::query()->create([
            'company_id' => $company->id,
            'product_code' => '22',
            'name' => 'Other',
            'default_annual_interest_rate_percent' => '0',
            'notes' => null,
            'is_active' => true,
        ]);

        $this->actingAs($owner)->post(route('finance.positions.store', ['category' => 'loan'], absolute: false), [
            'title' => 'PAR test loan',
            'principal' => '0',
            'annual_interest_rate_percent' => '0',
            'start_date' => '2026-01-01',
            'notes' => null,
            'member_id' => $member->id,
            'loan_product_id' => $product->id,
            'sanctioned_amount' => '1000',
        ])->assertSessionHasNoErrors();

        $position = FinancialPosition::query()->where('loan_product_id', $product->id)->firstOrFail();

        $this->actingAs($owner)->post(
            route('finance.positions.loan.approve', ['category' => 'loan', 'position' => $position->id], absolute: false),
        )->assertSessionHasNoErrors();

        $this->actingAs($owner)->post(
            route('finance.positions.movements.disburse', ['category' => 'loan', 'position' => $position->id], absolute: false),
            [
                'amount' => '200.00',
                'memo' => 'Draw',
                'transaction_date' => '2026-01-05',
                'debit_chart_account_id' => $receivable->id,
                'credit_chart_account_id' => $cash->id,
                'reference' => 'D1',
            ],
        )->assertSessionHasNoErrors();

        $this->actingAs($owner)
            ->get(route('reports.par-aging'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounting/Reports/ParAging')
                ->has('summary')
                ->where('summary.total_outstanding_cents', 20_000));
    }
}
