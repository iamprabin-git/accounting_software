<?php

namespace Tests\Feature;

use App\Models\BankReconciliationBatch;
use App\Models\BankStatementLine;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_imports_statement_and_matches_journal_line(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $cash = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1000',
            'name' => 'Bank',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $equity = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '3000',
            'name' => 'Equity',
            'type' => ChartAccount::TYPE_EQUITY,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $entry = JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'transaction_date' => now()->toDateString(),
            'status' => JournalEntry::STATUS_APPROVED,
            'posted_number' => 1,
            'reference' => 'DEP-1',
        ]);

        $jl = JournalLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_account_id' => $cash->id,
            'debit_cents' => 5000,
            'credit_cents' => 0,
            'description' => 'Deposit',
        ]);

        JournalLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_account_id' => $equity->id,
            'debit_cents' => 0,
            'credit_cents' => 5000,
            'description' => null,
        ]);

        $csv = "date,amount,description,reference\n".now()->toDateString().',50.00,Bank deposit,DEP-1';

        $this->actingAs($owner)
            ->post(route('bank-reconciliation.store'), [
                'chart_account_id' => $cash->id,
                'name' => 'Test import',
                'csv' => $csv,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $batch = BankReconciliationBatch::query()->latest('id')->first();
        $this->assertNotNull($batch);
        $this->assertSame(1, BankStatementLine::query()->where('bank_reconciliation_batch_id', $batch->id)->count());

        $stmt = BankStatementLine::query()->where('bank_reconciliation_batch_id', $batch->id)->first();
        $this->assertNotNull($stmt);

        $this->assertDatabaseHas('accounting_audit_logs', [
            'company_id' => $company->id,
            'action' => 'bank_reconciliation.import',
        ]);

        $this->actingAs($owner)
            ->post(route('bank-reconciliation.match', ['batch' => $batch->id]), [
                'bank_statement_line_id' => $stmt->id,
                'journal_line_id' => $jl->id,
            ])
            ->assertSessionHasNoErrors();

        $stmt->refresh();
        $this->assertDatabaseHas('bank_statement_line_matches', [
            'bank_statement_line_id' => $stmt->id,
            'journal_line_id' => $jl->id,
        ]);
        $this->assertNotNull($stmt->fresh()->reconciled_at);

        $this->assertDatabaseHas('accounting_audit_logs', [
            'company_id' => $company->id,
            'journal_entry_id' => $entry->id,
            'action' => 'bank_reconciliation.matched',
        ]);
    }

    public function test_auto_match_links_unique_candidate(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $cash = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1000',
            'name' => 'Bank',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $equity = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '3000',
            'name' => 'Equity',
            'type' => ChartAccount::TYPE_EQUITY,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $entry = JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'transaction_date' => '2026-04-05',
            'status' => JournalEntry::STATUS_APPROVED,
            'posted_number' => 7,
        ]);

        $jl = JournalLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_account_id' => $cash->id,
            'debit_cents' => 1200,
            'credit_cents' => 0,
            'description' => null,
        ]);

        JournalLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_account_id' => $equity->id,
            'debit_cents' => 0,
            'credit_cents' => 1200,
            'description' => null,
        ]);

        $batch = BankReconciliationBatch::query()->create([
            'company_id' => $company->id,
            'chart_account_id' => $cash->id,
            'user_id' => $owner->id,
            'name' => 'Auto',
        ]);

        BankStatementLine::query()->create([
            'bank_reconciliation_batch_id' => $batch->id,
            'transaction_date' => '2026-04-06',
            'amount_cents' => 1200,
            'description' => 'Stmt',
            'external_reference' => null,
        ]);

        $this->actingAs($owner)
            ->post(route('bank-reconciliation.auto-match', ['batch' => $batch->id]))
            ->assertSessionHasNoErrors();

        $stmt = BankStatementLine::query()->where('bank_reconciliation_batch_id', $batch->id)->first();
        $this->assertNotNull($stmt);
        $this->assertDatabaseHas('bank_statement_line_matches', [
            'bank_statement_line_id' => $stmt->id,
            'journal_line_id' => $jl->id,
        ]);
        $this->assertNotNull($stmt->fresh()->reconciled_at);
    }

    public function test_import_accepts_debit_and_credit_columns(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $cash = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1000',
            'name' => 'Bank',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $csv = "date,debit,credit,description\n2026-04-01,0,25.50,In";

        $this->actingAs($owner)
            ->post(route('bank-reconciliation.store'), [
                'chart_account_id' => $cash->id,
                'csv' => $csv,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseCount('bank_statement_lines', 1);

        $line = BankStatementLine::query()->latest('id')->first();
        $this->assertNotNull($line);
        $this->assertSame(2550, (int) $line->amount_cents);
    }

    public function test_multi_line_match_sums_to_statement_amount(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $cash = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1000',
            'name' => 'Bank',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $equity = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '3000',
            'name' => 'Equity',
            'type' => ChartAccount::TYPE_EQUITY,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $e1 = JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'transaction_date' => now()->toDateString(),
            'status' => JournalEntry::STATUS_APPROVED,
            'posted_number' => 1,
        ]);
        $jl40 = JournalLine::query()->create([
            'journal_entry_id' => $e1->id,
            'chart_account_id' => $cash->id,
            'debit_cents' => 4000,
            'credit_cents' => 0,
            'description' => null,
        ]);
        JournalLine::query()->create([
            'journal_entry_id' => $e1->id,
            'chart_account_id' => $equity->id,
            'debit_cents' => 0,
            'credit_cents' => 4000,
            'description' => null,
        ]);

        $e2 = JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'transaction_date' => now()->toDateString(),
            'status' => JournalEntry::STATUS_APPROVED,
            'posted_number' => 2,
        ]);
        $jl60 = JournalLine::query()->create([
            'journal_entry_id' => $e2->id,
            'chart_account_id' => $cash->id,
            'debit_cents' => 6000,
            'credit_cents' => 0,
            'description' => null,
        ]);
        JournalLine::query()->create([
            'journal_entry_id' => $e2->id,
            'chart_account_id' => $equity->id,
            'debit_cents' => 0,
            'credit_cents' => 6000,
            'description' => null,
        ]);

        $batch = BankReconciliationBatch::query()->create([
            'company_id' => $company->id,
            'chart_account_id' => $cash->id,
            'user_id' => $owner->id,
        ]);
        $stmt = BankStatementLine::query()->create([
            'bank_reconciliation_batch_id' => $batch->id,
            'transaction_date' => now()->toDateString(),
            'amount_cents' => 10000,
            'description' => 'Batch',
        ]);

        $this->actingAs($owner)
            ->post(route('bank-reconciliation.match', ['batch' => $batch->id]), [
                'bank_statement_line_id' => $stmt->id,
                'journal_line_id' => $jl40->id,
            ])
            ->assertSessionHasNoErrors();

        $stmt->refresh();
        $this->assertNull($stmt->reconciled_at);

        $this->actingAs($owner)
            ->post(route('bank-reconciliation.match', ['batch' => $batch->id]), [
                'bank_statement_line_id' => $stmt->id,
                'journal_line_id' => $jl60->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($stmt->fresh()->reconciled_at);
        $this->assertSame(2, $stmt->matches()->count());
    }
}
