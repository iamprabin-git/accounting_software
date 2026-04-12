<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\FinancialPosition;
use App\Models\FinancialPositionMovement;
use App\Models\JournalEntry;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructuredLoanWorkflowTest extends TestCase
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

    public function test_structured_loan_flow_product_apply_approve_disburse_journal(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        [$cash, $receivable] = $this->cashAndReceivable($company, $owner);
        $member = $this->approvedMember($company, $owner);

        $product = LoanProduct::query()->create([
            'company_id' => $company->id,
            'product_code' => '11',
            'name' => 'Standard term',
            'default_annual_interest_rate_percent' => '10',
            'notes' => null,
            'is_active' => true,
        ]);

        $this->actingAs($owner)->post(route('finance.positions.store', ['category' => 'loan'], absolute: false), [
            'title' => 'Member term loan',
            'principal' => '0',
            'annual_interest_rate_percent' => '10',
            'start_date' => null,
            'notes' => null,
            'member_id' => $member->id,
            'loan_product_id' => $product->id,
            'sanctioned_amount' => '5000',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $position = FinancialPosition::query()->where('loan_product_id', $product->id)->firstOrFail();
        $this->assertSame(FinancialPosition::LOAN_WORKFLOW_PENDING_APPROVAL, $position->loan_workflow_status);
        $this->assertNull($position->account_number);
        $this->assertSame(1, $position->product_account_sequence);
        $this->assertSame('11-0001', $position->proposedAccountNumber());
        $this->assertSame(0, $position->principal_cents);
        $this->assertSame(0, FinancialPositionMovement::query()->where('financial_position_id', $position->id)->count());

        $this->actingAs($owner)->post(
            route('finance.positions.loan.approve', [
                'category' => 'loan',
                'position' => $position->id,
            ], absolute: false),
        )->assertSessionHasNoErrors();

        $position->refresh();
        $this->assertSame(FinancialPosition::LOAN_WORKFLOW_ACTIVE, $position->loan_workflow_status);
        $this->assertSame('11-0001', $position->account_number);

        $this->actingAs($owner)->post(
            route('finance.positions.movements.disburse', [
                'category' => 'loan',
                'position' => $position->id,
            ], absolute: false),
            [
                'amount' => '1000.00',
                'memo' => 'First draw',
                'transaction_date' => '2026-04-06',
                'debit_chart_account_id' => $receivable->id,
                'credit_chart_account_id' => $cash->id,
                'reference' => 'DISB-1',
            ],
        )->assertSessionHasNoErrors()->assertRedirect();

        $position->refresh();
        $this->assertSame(100_000, $position->principal_cents);

        $movement = FinancialPositionMovement::query()
            ->where('financial_position_id', $position->id)
            ->where('type', FinancialPositionMovement::TYPE_DISBURSEMENT)
            ->firstOrFail();
        $this->assertNotNull($movement->journal_entry_id);

        $entry = JournalEntry::query()->findOrFail($movement->journal_entry_id);
        $this->assertSame($member->id, $entry->member_id);
        $this->assertSame('loan', $entry->finance_category);
    }
}
