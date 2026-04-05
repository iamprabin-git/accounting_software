<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\FinancialPosition;
use App\Models\FinancialPositionAccrual;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceAccrualsTest extends TestCase
{
    use RefreshDatabase;

    private function approvedAccounts(Company $company, User $owner): array
    {
        $expense = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '6100',
            'name' => 'Interest expense',
            'type' => ChartAccount::TYPE_EXPENSE,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $liability = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '2200',
            'name' => 'Loan payable',
            'type' => ChartAccount::TYPE_LIABILITY,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $asset = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1100',
            'name' => 'Cash',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $revenue = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '4100',
            'name' => 'Interest income',
            'type' => ChartAccount::TYPE_REVENUE,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        return [$expense, $liability, $asset, $revenue];
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

    public function test_loan_sync_year_and_post_single_month_to_ledger(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        [$expense, $liability] = $this->approvedAccounts($company, $owner);
        $member = $this->approvedMember($company, $owner);

        $this->actingAs($owner)->post(route('finance.positions.store', ['category' => 'loan'], absolute: false), [
            'title' => 'Term loan',
            'principal' => '12000',
            'annual_interest_rate_percent' => '12',
            'start_date' => null,
            'notes' => null,
            'member_id' => $member->id,
        ])->assertSessionHasNoErrors();

        $position = FinancialPosition::query()->first();
        $this->assertNotNull($position);
        $this->assertSame(FinancialPosition::CATEGORY_LOAN, $position->category);

        $year = 2026;
        $this->actingAs($owner)
            ->post(
                route('finance.positions.accruals.sync-year', [
                    'category' => 'loan',
                    'position' => $position->id,
                ], absolute: false),
                ['year' => $year],
            )
            ->assertSessionHasNoErrors();

        $this->assertSame(12, FinancialPositionAccrual::query()->where('financial_position_id', $position->id)->count());

        $jan = FinancialPositionAccrual::query()
            ->where('financial_position_id', $position->id)
            ->where('accrual_year', $year)
            ->where('accrual_month', 1)
            ->where('kind', FinancialPositionAccrual::KIND_LOAN_MONTHLY)
            ->first();
        $this->assertNotNull($jan);
        $this->assertGreaterThan(0, $jan->amount_cents);

        $this->actingAs($owner)
            ->post(
                route('finance.positions.accruals.post-ledger', [
                    'category' => 'loan',
                    'position' => $position->id,
                    'accrual' => $jan->id,
                ], absolute: false),
                [
                    'transaction_date' => "{$year}-01-15",
                    'debit_chart_account_id' => $expense->id,
                    'credit_chart_account_id' => $liability->id,
                    'reference' => 'INT-JAN',
                ],
            )
            ->assertSessionHasNoErrors();

        $jan->refresh();
        $this->assertNotNull($jan->journal_entry_id);
        $entry = JournalEntry::query()->find($jan->journal_entry_id);
        $this->assertNotNull($entry);
        $this->assertSame(JournalEntry::STATUS_APPROVED, $entry->status);
        $this->assertSame($member->id, $entry->member_id);
        $this->assertSame(FinancialPosition::CATEGORY_LOAN, $entry->finance_category);
    }

    public function test_savings_quarter_post_links_one_journal_to_three_months(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        [, , $asset, $revenue] = $this->approvedAccounts($company, $owner);
        $member = $this->approvedMember($company, $owner);

        $this->actingAs($owner)->post(route('finance.positions.store', ['category' => 'savings'], absolute: false), [
            'title' => 'Savings',
            'principal' => '9000',
            'annual_interest_rate_percent' => '4',
            'start_date' => null,
            'notes' => null,
            'member_id' => $member->id,
        ])->assertSessionHasNoErrors();

        $position = FinancialPosition::query()->first();
        $this->assertNotNull($position);

        $year = 2026;
        $this->actingAs($owner)
            ->post(
                route('finance.positions.accruals.sync-year', [
                    'category' => 'savings',
                    'position' => $position->id,
                ], absolute: false),
                ['year' => $year],
            )
            ->assertSessionHasNoErrors();

        $this->actingAs($owner)
            ->post(
                route('finance.positions.accruals.post-savings-quarter', [
                    'category' => 'savings',
                    'position' => $position->id,
                ], absolute: false),
                [
                    'year' => $year,
                    'quarter' => 1,
                    'transaction_date' => "{$year}-03-31",
                    'debit_chart_account_id' => $asset->id,
                    'credit_chart_account_id' => $revenue->id,
                    'reference' => 'Q1-INT',
                ],
            )
            ->assertSessionHasNoErrors();

        $q1 = FinancialPositionAccrual::query()
            ->where('financial_position_id', $position->id)
            ->where('accrual_year', $year)
            ->whereIn('accrual_month', [1, 2, 3])
            ->where('kind', FinancialPositionAccrual::KIND_SAVINGS_MONTHLY)
            ->get();

        $this->assertCount(3, $q1);
        $jid = $q1->first()->journal_entry_id;
        $this->assertNotNull($jid);
        foreach ($q1 as $row) {
            $this->assertSame($jid, $row->journal_entry_id);
        }
        $entry = JournalEntry::query()->find($jid);
        $this->assertNotNull($entry);
        $this->assertSame($member->id, $entry->member_id);
        $this->assertSame(FinancialPosition::CATEGORY_SAVINGS, $entry->finance_category);
    }

    public function test_investment_manual_accrual_without_annual_rate_on_store(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        [$expense, , $asset] = $this->approvedAccounts($company, $owner);
        $member = $this->approvedMember($company, $owner);

        $this->actingAs($owner)->post(route('finance.positions.store', ['category' => 'investment'], absolute: false), [
            'title' => 'Private note',
            'principal' => '5000',
            'start_date' => null,
            'notes' => null,
            'member_id' => $member->id,
        ])->assertSessionHasNoErrors();

        $position = FinancialPosition::query()->first();
        $this->assertNotNull($position);
        $this->assertSame('0.0000', $position->annual_interest_rate_percent);

        $year = 2026;
        $this->actingAs($owner)
            ->post(
                route('finance.positions.accruals.manual', [
                    'category' => 'investment',
                    'position' => $position->id,
                ], absolute: false),
                [
                    'year' => $year,
                    'month' => 4,
                    'amount' => '125.50',
                ],
            )
            ->assertSessionHasNoErrors();

        $accrual = FinancialPositionAccrual::query()
            ->where('financial_position_id', $position->id)
            ->where('kind', FinancialPositionAccrual::KIND_INVESTMENT_MANUAL)
            ->first();
        $this->assertNotNull($accrual);
        $this->assertSame(12_550, $accrual->amount_cents);

        $this->actingAs($owner)
            ->post(
                route('finance.positions.accruals.post-ledger', [
                    'category' => 'investment',
                    'position' => $position->id,
                    'accrual' => $accrual->id,
                ], absolute: false),
                [
                    'transaction_date' => "{$year}-04-30",
                    'debit_chart_account_id' => $asset->id,
                    'credit_chart_account_id' => $expense->id,
                ],
            )
            ->assertSessionHasNoErrors();

        $accrual->refresh();
        $this->assertNotNull($accrual->journal_entry_id);
        $entry = JournalEntry::query()->find($accrual->journal_entry_id);
        $this->assertNotNull($entry);
        $this->assertSame($member->id, $entry->member_id);
        $this->assertSame(FinancialPosition::CATEGORY_INVESTMENT, $entry->finance_category);
    }

    public function test_loan_store_requires_member_id_and_approved_status(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)->post(route('finance.positions.store', ['category' => 'loan'], absolute: false), [
            'title' => 'Term loan',
            'principal' => '1000',
            'annual_interest_rate_percent' => '5',
            'start_date' => null,
            'notes' => null,
        ])->assertSessionHasErrors('member_id');

        $pending = Member::query()->create([
            'company_id' => $company->id,
            'reference_code' => 'PEND',
            'name' => 'Pending',
            'status' => Member::STATUS_PENDING,
            'created_by_user_id' => $owner->id,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ]);

        $this->actingAs($owner)->post(route('finance.positions.store', ['category' => 'loan'], absolute: false), [
            'title' => 'Term loan',
            'principal' => '1000',
            'annual_interest_rate_percent' => '5',
            'start_date' => null,
            'notes' => null,
            'member_id' => $pending->id,
        ])->assertSessionHasErrors('member_id');
    }

    public function test_loan_sync_year_blocked_when_position_has_no_approved_member(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $position = FinancialPosition::query()->create([
            'company_id' => $company->id,
            'member_id' => null,
            'category' => FinancialPosition::CATEGORY_LOAN,
            'title' => 'Unlinked loan',
            'principal_cents' => 100_000,
            'annual_interest_rate_percent' => '12',
            'start_date' => null,
            'notes' => null,
        ]);

        $this->actingAs($owner)
            ->post(
                route('finance.positions.accruals.sync-year', [
                    'category' => 'loan',
                    'position' => $position->id,
                ], absolute: false),
                ['year' => 2026],
            )
            ->assertSessionHasErrors('member');
    }
}
