<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\FinancialPosition;
use App\Models\FinancialPositionMovement;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceProductMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_savings_deposit_posts_cash_dr_and_member_cr(): void
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

        $cash = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1000',
            'name' => 'Cash on hand',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $position = FinancialPosition::query()->create([
            'company_id' => $company->id,
            'member_id' => $member->id,
            'category' => FinancialPosition::CATEGORY_SAVINGS,
            'title' => 'Saver',
            'account_number' => 'SV-LEG-1',
            'principal_cents' => 10_000,
            'annual_interest_rate_percent' => '4',
            'start_date' => null,
            'notes' => null,
        ]);

        FinancialPositionMovement::query()->create([
            'financial_position_id' => $position->id,
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'type' => FinancialPositionMovement::TYPE_OPENING,
            'amount_cents' => 10_000,
            'balance_after_cents' => 10_000,
            'memo' => 'Opening balance',
        ]);

        $this->actingAs($owner)
            ->post(
                route('finance.positions.movements.deposit', [
                    'category' => 'savings',
                    'position' => $position->id,
                ], absolute: false),
                [
                    'amount' => '50.00',
                    'memo' => 'Top-up',
                    'transaction_date' => now()->toDateString(),
                    'debit_chart_account_id' => $cash->id,
                    'credit_chart_account_id' => '',
                    'reference' => 'T1',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $position->refresh();
        $this->assertSame(15_000, $position->principal_cents);

        $last = FinancialPositionMovement::query()
            ->where('financial_position_id', $position->id)
            ->orderByDesc('id')
            ->first();
        $this->assertSame(FinancialPositionMovement::TYPE_DEPOSIT, $last->type);
        $this->assertSame(5_000, $last->amount_cents);
        $this->assertSame(15_000, $last->balance_after_cents);
        $this->assertNotNull($last->journal_entry_id);

        $entry = JournalEntry::query()->findOrFail($last->journal_entry_id);
        $this->assertSame(JournalEntry::STATUS_APPROVED, $entry->status);
        $lines = $entry->lines()->orderBy('id')->get();
        $this->assertCount(2, $lines);
        $this->assertSame($cash->id, (int) $lines[0]->chart_account_id);
        $this->assertSame(5_000, (int) $lines[0]->debit_cents);
        $this->assertSame(0, (int) $lines[0]->credit_cents);
        $this->assertSame(0, (int) $lines[1]->debit_cents);
        $this->assertSame(5_000, (int) $lines[1]->credit_cents);
    }

    public function test_legacy_loan_disbursement_posts_member_dr_and_cash_cr(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        $member = Member::query()->create([
            'company_id' => $company->id,
            'reference_code' => 'M2',
            'name' => 'Loan Member',
            'status' => Member::STATUS_APPROVED,
            'created_by_user_id' => $owner->id,
            'approved_by_user_id' => $owner->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $cash = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1001',
            'name' => 'Cash Counter',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $position = FinancialPosition::query()->create([
            'company_id' => $company->id,
            'member_id' => $member->id,
            'category' => FinancialPosition::CATEGORY_LOAN,
            'title' => 'Loan A',
            'account_number' => 'LN-LEG-1',
            'principal_cents' => 20_000,
            'annual_interest_rate_percent' => '12',
            'start_date' => null,
            'notes' => null,
        ]);

        FinancialPositionMovement::query()->create([
            'financial_position_id' => $position->id,
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'type' => FinancialPositionMovement::TYPE_OPENING,
            'amount_cents' => 20_000,
            'balance_after_cents' => 20_000,
            'memo' => 'Opening balance',
        ]);

        $this->actingAs($owner)
            ->post(
                route('finance.positions.movements.deposit', [
                    'category' => 'loan',
                    'position' => $position->id,
                ], absolute: false),
                [
                    'amount' => '50.00',
                    'memo' => 'Disburse',
                    'transaction_date' => now()->toDateString(),
                    'credit_chart_account_id' => $cash->id,
                    'debit_chart_account_id' => '',
                    'reference' => 'LD1',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $position->refresh();
        $this->assertSame(25_000, $position->principal_cents);

        $last = FinancialPositionMovement::query()
            ->where('financial_position_id', $position->id)
            ->orderByDesc('id')
            ->first();
        $this->assertSame(FinancialPositionMovement::TYPE_DEPOSIT, $last->type);
        $this->assertSame(5_000, $last->amount_cents);
        $this->assertSame(25_000, $last->balance_after_cents);
        $this->assertNotNull($last->journal_entry_id);

        $entry = JournalEntry::query()->findOrFail($last->journal_entry_id);
        $this->assertSame(JournalEntry::STATUS_APPROVED, $entry->status);
        $lines = $entry->lines()->orderBy('id')->get();
        $this->assertCount(2, $lines);
        $this->assertSame(5_000, (int) $lines[0]->debit_cents);
        $this->assertSame(0, (int) $lines[0]->credit_cents);
        $this->assertSame($cash->id, (int) $lines[1]->chart_account_id);
        $this->assertSame(0, (int) $lines[1]->debit_cents);
        $this->assertSame(5_000, (int) $lines[1]->credit_cents);
    }
}
