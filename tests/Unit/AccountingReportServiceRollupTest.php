<?php

namespace Tests\Unit;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\FinancialPosition;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\User;
use App\Services\AccountingReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportServiceRollupTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_balance_rolls_member_subledgers_into_sigma_rows(): void
    {
        [$company, $owner, $loanAccount, $savingsAccount] = $this->seedMemberLedgerData();

        $report = (new AccountingReportService($company->id))->trialBalance(now(), showZero: false);

        $codes = collect($report['accounts'])->pluck('code')->all();
        $this->assertContains('Σ-LOANS', $codes);
        $this->assertContains('Σ-SAVINGS', $codes);
        $this->assertNotContains($loanAccount->code, $codes);
        $this->assertNotContains($savingsAccount->code, $codes);

        $loanRow = collect($report['accounts'])->firstWhere('code', 'Σ-LOANS');
        $this->assertSame(10_000, (int) $loanRow['debit_cents']);
        $this->assertSame(0, (int) $loanRow['credit_cents']);

        $savingsRow = collect($report['accounts'])->firstWhere('code', 'Σ-SAVINGS');
        $this->assertSame(0, (int) $savingsRow['debit_cents']);
        $this->assertSame(7_000, (int) $savingsRow['credit_cents']);

        $this->assertSame(10_000, (int) $report['member_loan_principal_cents']);
        $this->assertSame(7_000, (int) $report['member_savings_deposits_cents']);
        $this->assertSame(
            (int) $report['totals']['debit_cents'],
            (int) $report['totals']['credit_cents'],
        );
    }

    public function test_balance_sheet_rolls_member_subledgers_into_sigma_rows(): void
    {
        [$company] = $this->seedMemberLedgerData();

        $report = (new AccountingReportService($company->id))->balanceSheet(now(), showZero: false);

        $assetCodes = collect($report['assets'])->pluck('code')->all();
        $liabilityCodes = collect($report['liabilities'])->pluck('code')->all();

        $this->assertContains('Σ-LOANS', $assetCodes);
        $this->assertContains('Σ-SAVINGS', $liabilityCodes);

        $loanRow = collect($report['assets'])->firstWhere('code', 'Σ-LOANS');
        $savingsRow = collect($report['liabilities'])->firstWhere('code', 'Σ-SAVINGS');
        $this->assertSame(10_000, (int) $loanRow['balance_cents']);
        $this->assertSame(7_000, (int) $savingsRow['balance_cents']);
    }

    public function test_trial_balance_counts_unapproved_member_personal_account_when_posted(): void
    {
        [$company, , $loanAccount] = $this->seedMemberLedgerData();

        $loanAccount->update([
            'approval_status' => ChartAccount::STATUS_PENDING,
            'approved_at' => null,
            'approved_by_user_id' => null,
            'approved_by_admin_id' => null,
        ]);

        $report = (new AccountingReportService($company->id))->trialBalance(now(), showZero: false);

        $loanRow = collect($report['accounts'])->firstWhere('code', 'Σ-LOANS');
        $this->assertNotNull($loanRow);
        $this->assertSame(10_000, (int) $loanRow['debit_cents']);
        $this->assertSame(0, (int) $loanRow['credit_cents']);
    }

    public function test_balance_sheet_counts_unapproved_member_personal_account_when_posted(): void
    {
        [$company, , $loanAccount] = $this->seedMemberLedgerData();

        $loanAccount->update([
            'approval_status' => ChartAccount::STATUS_PENDING,
            'approved_at' => null,
            'approved_by_user_id' => null,
            'approved_by_admin_id' => null,
        ]);

        $report = (new AccountingReportService($company->id))->balanceSheet(now(), showZero: false);

        $loanRow = collect($report['assets'])->firstWhere('code', 'Σ-LOANS');
        $this->assertNotNull($loanRow);
        $this->assertSame(10_000, (int) $loanRow['balance_cents']);
    }

    /**
     * @return array{0: Company, 1: User, 2: ChartAccount, 3: ChartAccount}
     */
    private function seedMemberLedgerData(): array
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $cash = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $loanSub = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => 'LN-0001',
            'name' => 'Loan personal Member',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $savingsSub = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => 'SV-0001',
            'name' => 'Savings personal Member',
            'type' => ChartAccount::TYPE_LIABILITY,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $member = Member::query()->create([
            'company_id' => $company->id,
            'reference_code' => 'CID-1',
            'name' => 'Member One',
            'email' => 'm1@example.test',
            'status' => Member::STATUS_APPROVED,
            'created_by_user_id' => $owner->id,
            'approved_by_user_id' => $owner->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        FinancialPosition::query()->create([
            'company_id' => $company->id,
            'member_id' => $member->id,
            'category' => FinancialPosition::CATEGORY_LOAN,
            'account_number' => 'LN-0001',
            'title' => 'Loan position',
            'principal_cents' => 10_000,
            'annual_interest_rate_percent' => 12,
        ]);

        FinancialPosition::query()->create([
            'company_id' => $company->id,
            'member_id' => $member->id,
            'category' => FinancialPosition::CATEGORY_SAVINGS,
            'account_number' => 'SV-0001',
            'title' => 'Savings position',
            'principal_cents' => 7_000,
            'annual_interest_rate_percent' => 4,
        ]);

        $loanEntry = JournalEntry::query()->create([
            'company_id' => $company->id,
            'member_id' => $member->id,
            'finance_category' => FinancialPosition::CATEGORY_LOAN,
            'user_id' => $owner->id,
            'memo' => 'Loan disbursement',
            'transaction_date' => now()->toDateString(),
            'status' => JournalEntry::STATUS_APPROVED,
            'approved_by_user_id' => $owner->id,
            'approved_at' => now(),
            'posted_number' => 1,
        ]);
        $loanEntry->lines()->createMany([
            ['chart_account_id' => $loanSub->id, 'debit_cents' => 10_000, 'credit_cents' => 0],
            ['chart_account_id' => $cash->id, 'debit_cents' => 0, 'credit_cents' => 10_000],
        ]);

        $savingsEntry = JournalEntry::query()->create([
            'company_id' => $company->id,
            'member_id' => $member->id,
            'finance_category' => FinancialPosition::CATEGORY_SAVINGS,
            'user_id' => $owner->id,
            'memo' => 'Savings deposit',
            'transaction_date' => now()->toDateString(),
            'status' => JournalEntry::STATUS_APPROVED,
            'approved_by_user_id' => $owner->id,
            'approved_at' => now(),
            'posted_number' => 2,
        ]);
        $savingsEntry->lines()->createMany([
            ['chart_account_id' => $cash->id, 'debit_cents' => 7_000, 'credit_cents' => 0],
            ['chart_account_id' => $savingsSub->id, 'debit_cents' => 0, 'credit_cents' => 7_000],
        ]);

        return [$company, $owner, $loanSub, $savingsSub];
    }
}

