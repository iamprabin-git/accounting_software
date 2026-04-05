<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\FinancialPosition;
use App\Models\FinancialPositionMovement;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\SavingsProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructuredSavingsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: ChartAccount, 1: ChartAccount}
     */
    private function cashAndSavingsLiability(Company $company, User $owner): array
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

        $liability = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '2300',
            'name' => 'Member savings',
            'type' => ChartAccount::TYPE_LIABILITY,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        return [$cash, $liability];
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

    public function test_structured_savings_flow_product_apply_approve_deposit_journal(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        [$cash, $liability] = $this->cashAndSavingsLiability($company, $owner);
        $member = $this->approvedMember($company, $owner);

        $product = SavingsProduct::query()->create([
            'company_id' => $company->id,
            'product_code' => '01',
            'name' => 'Regular savings',
            'default_annual_interest_rate_percent' => '5',
            'notes' => null,
            'is_active' => true,
        ]);

        $this->actingAs($owner)->post(route('finance.positions.store', ['category' => 'savings'], absolute: false), [
            'title' => 'Member savings',
            'principal' => '0',
            'annual_interest_rate_percent' => '5',
            'start_date' => null,
            'notes' => null,
            'member_id' => $member->id,
            'savings_product_id' => $product->id,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $position = FinancialPosition::query()->where('savings_product_id', $product->id)->firstOrFail();
        $this->assertSame(FinancialPosition::LOAN_WORKFLOW_PENDING_APPROVAL, $position->savings_workflow_status);
        $this->assertNull($position->account_number);
        $this->assertSame(1, $position->savings_product_account_sequence);
        $this->assertSame('01-0001', $position->proposedSavingsAccountNumber());
        $this->assertSame(0, $position->principal_cents);
        $this->assertSame(0, FinancialPositionMovement::query()->where('financial_position_id', $position->id)->count());

        $this->actingAs($owner)->post(
            route('finance.positions.savings.approve', [
                'category' => 'savings',
                'position' => $position->id,
            ], absolute: false),
        )->assertSessionHasNoErrors();

        $position->refresh();
        $this->assertSame(FinancialPosition::LOAN_WORKFLOW_ACTIVE, $position->savings_workflow_status);
        $this->assertSame('01-0001', $position->account_number);

        $this->actingAs($owner)->post(
            route('finance.positions.movements.savings-deposit', [
                'category' => 'savings',
                'position' => $position->id,
            ], absolute: false),
            [
                'amount' => '500.00',
                'memo' => 'Opening deposit',
                'transaction_date' => '2026-04-04',
                'debit_chart_account_id' => $cash->id,
                'credit_chart_account_id' => $liability->id,
                'reference' => 'DEP-1',
            ],
        )->assertSessionHasNoErrors()->assertRedirect();

        $position->refresh();
        $this->assertSame(50_000, $position->principal_cents);

        $movement = FinancialPositionMovement::query()
            ->where('financial_position_id', $position->id)
            ->where('type', FinancialPositionMovement::TYPE_DEPOSIT)
            ->firstOrFail();
        $this->assertNotNull($movement->journal_entry_id);

        $entry = JournalEntry::query()->findOrFail($movement->journal_entry_id);
        $this->assertSame($member->id, $entry->member_id);
        $this->assertSame('savings', $entry->finance_category);
    }
}
