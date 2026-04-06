<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_creates_draft_company_approves_and_reports_use_approved_only(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        $staff = User::factory()->staff($company)->create();

        $cash = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1000',
            'name' => 'Cash',
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

        $this->actingAs($staff)->post('/journals', [
            'transaction_date' => now()->toDateString(),
            'lines' => [
                [
                    'chart_account_id' => $cash->id,
                    'debit' => 50,
                    'credit' => 0,
                ],
                [
                    'chart_account_id' => $equity->id,
                    'debit' => 0,
                    'credit' => 50,
                ],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $entry = JournalEntry::query()->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame(JournalEntry::STATUS_DRAFT, $entry->status);

        $this->actingAs($staff)
            ->post(route('journals.submit', ['journal' => $entry->id]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $entry->refresh();
        $this->assertSame(JournalEntry::STATUS_PENDING, $entry->status);

        $this->actingAs($owner)
            ->post(route('journals.approve', ['journal' => $entry->id]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $entry->refresh();
        $this->assertSame(JournalEntry::STATUS_APPROVED, $entry->status);
        $this->assertSame($owner->id, $entry->approved_by_user_id);
        $this->assertNotNull($entry->posted_number);

        $this->assertDatabaseHas('accounting_audit_logs', [
            'company_id' => $company->id,
            'journal_entry_id' => $entry->id,
            'action' => 'journal.created_draft',
        ]);
        $this->assertDatabaseHas('accounting_audit_logs', [
            'company_id' => $company->id,
            'journal_entry_id' => $entry->id,
            'action' => 'journal.submitted',
        ]);
        $this->assertDatabaseHas('accounting_audit_logs', [
            'company_id' => $company->id,
            'journal_entry_id' => $entry->id,
            'action' => 'journal.approved',
        ]);

        $this->actingAs($owner)
            ->get(route('reports.trial-balance', absolute: false))
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('reports.trial-balance', absolute: false))
            ->assertOk();
    }

    public function test_company_user_can_create_journal_draft_via_http(): void
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

        $equity = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '3000',
            'name' => 'Equity',
            'type' => ChartAccount::TYPE_EQUITY,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $this->actingAs($owner)->post('/journals', [
            'transaction_date' => now()->toDateString(),
            'lines' => [
                ['chart_account_id' => $cash->id, 'debit' => 10, 'credit' => 0],
                ['chart_account_id' => $equity->id, 'debit' => 0, 'credit' => 10],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $entry = JournalEntry::query()->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame(JournalEntry::STATUS_DRAFT, $entry->status);
    }

    public function test_unbalanced_draft_journal_cannot_be_submitted(): void
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
            'status' => JournalEntry::STATUS_DRAFT,
        ]);

        JournalLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_account_id' => $cash->id,
            'debit_cents' => 10_000,
            'credit_cents' => 0,
        ]);

        JournalLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_account_id' => $equity->id,
            'debit_cents' => 0,
            'credit_cents' => 5_000,
        ]);

        $this->actingAs($owner)
            ->from(route('journals.show', ['journal' => $entry->id]))
            ->post(route('journals.submit', ['journal' => $entry->id]))
            ->assertSessionHasErrors('submit');

        $this->assertSame(JournalEntry::STATUS_DRAFT, $entry->fresh()->status);
    }

    public function test_approval_assigns_company_posted_number_sequence(): void
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

        $equity = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '3000',
            'name' => 'Equity',
            'type' => ChartAccount::TYPE_EQUITY,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $makeDraft = function (int $amountCents) use ($company, $owner, $cash, $equity): JournalEntry {
            $entry = JournalEntry::query()->create([
                'company_id' => $company->id,
                'user_id' => $owner->id,
                'transaction_date' => now()->toDateString(),
                'status' => JournalEntry::STATUS_DRAFT,
            ]);

            JournalLine::query()->create([
                'journal_entry_id' => $entry->id,
                'chart_account_id' => $cash->id,
                'debit_cents' => $amountCents,
                'credit_cents' => 0,
            ]);
            JournalLine::query()->create([
                'journal_entry_id' => $entry->id,
                'chart_account_id' => $equity->id,
                'debit_cents' => 0,
                'credit_cents' => $amountCents,
            ]);

            return $entry;
        };

        $first = $makeDraft(10_000);
        $second = $makeDraft(20_000);

        $this->actingAs($owner)->post(route('journals.submit', ['journal' => $first->id]))->assertRedirect();
        $this->actingAs($owner)->post(route('journals.submit', ['journal' => $second->id]))->assertRedirect();

        $this->actingAs($owner)->post(route('journals.approve', ['journal' => $first->id]))->assertRedirect();
        $this->actingAs($owner)->post(route('journals.approve', ['journal' => $second->id]))->assertRedirect();

        $this->assertSame(1, $first->fresh()->posted_number);
        $this->assertSame(2, $second->fresh()->posted_number);
    }

    public function test_locked_period_blocks_journal_approval(): void
    {
        $company = Company::factory()->create([
            'journal_lock_date' => now()->toDateString(),
        ]);
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
            'status' => JournalEntry::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        JournalLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_account_id' => $cash->id,
            'debit_cents' => 10_000,
            'credit_cents' => 0,
        ]);
        JournalLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_account_id' => $equity->id,
            'debit_cents' => 0,
            'credit_cents' => 10_000,
        ]);

        $this->actingAs($owner)
            ->post(route('journals.approve', ['journal' => $entry->id]))
            ->assertSessionHasErrors('approve');

        $this->assertSame(JournalEntry::STATUS_PENDING, $entry->fresh()->status);
        $this->assertNull($entry->fresh()->posted_number);
    }

    public function test_approved_journal_can_be_reversed_into_balanced_draft(): void
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
            'approved_by_user_id' => $owner->id,
            'approved_at' => now(),
            'posted_number' => 1,
        ]);

        JournalLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_account_id' => $cash->id,
            'debit_cents' => 15_000,
            'credit_cents' => 0,
        ]);
        JournalLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_account_id' => $equity->id,
            'debit_cents' => 0,
            'credit_cents' => 15_000,
        ]);

        $this->actingAs($owner)
            ->post(route('journals.reverse', ['journal' => $entry->id]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $reversal = JournalEntry::query()
            ->where('reversal_of_journal_entry_id', $entry->id)
            ->with('lines')
            ->first();

        $this->assertNotNull($reversal);
        $this->assertSame(JournalEntry::STATUS_DRAFT, $reversal->status);
        $this->assertCount(2, $reversal->lines);
        $this->assertSame(15_000, (int) $reversal->lines[0]->credit_cents + (int) $reversal->lines[1]->credit_cents);
        $this->assertSame(15_000, (int) $reversal->lines[0]->debit_cents + (int) $reversal->lines[1]->debit_cents);

        $this->assertDatabaseHas('accounting_audit_logs', [
            'company_id' => $company->id,
            'journal_entry_id' => $entry->id,
            'action' => 'journal.reversal_created',
        ]);
        $this->assertDatabaseHas('accounting_audit_logs', [
            'company_id' => $company->id,
            'journal_entry_id' => $reversal->id,
            'action' => 'journal.created_reversal_draft',
        ]);
    }

    public function test_high_value_journal_requires_two_distinct_approvers(): void
    {
        $company = Company::factory()->create([
            'dual_approval_threshold_cents' => 10_000,
        ]);
        $ownerA = User::factory()->companyOwner($company)->create();
        $ownerB = User::factory()->companyOwner($company)->create();

        $cash = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $ownerA->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
        $equity = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $ownerA->id,
            'code' => '3000',
            'name' => 'Equity',
            'type' => ChartAccount::TYPE_EQUITY,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $entry = JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $ownerA->id,
            'transaction_date' => now()->toDateString(),
            'status' => JournalEntry::STATUS_PENDING,
            'submitted_at' => now(),
        ]);
        JournalLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_account_id' => $cash->id,
            'debit_cents' => 10_000,
            'credit_cents' => 0,
        ]);
        JournalLine::query()->create([
            'journal_entry_id' => $entry->id,
            'chart_account_id' => $equity->id,
            'debit_cents' => 0,
            'credit_cents' => 10_000,
        ]);

        $this->actingAs($ownerA)
            ->post(route('journals.approve', ['journal' => $entry->id]), [
                'approval_comment' => 'First approval',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $entry->refresh();
        $this->assertSame(JournalEntry::STATUS_PENDING, $entry->status);
        $this->assertSame($ownerA->id, $entry->first_approved_by_user_id);
        $this->assertNull($entry->approved_by_user_id);

        $this->actingAs($ownerA)
            ->post(route('journals.approve', ['journal' => $entry->id]))
            ->assertForbidden();

        $this->actingAs($ownerB)
            ->post(route('journals.approve', ['journal' => $entry->id]), [
                'approval_comment' => 'Second approval',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $entry->refresh();
        $this->assertSame(JournalEntry::STATUS_APPROVED, $entry->status);
        $this->assertSame($ownerB->id, $entry->approved_by_user_id);
        $this->assertNotNull($entry->posted_number);
        $this->assertDatabaseHas('journal_approval_comments', [
            'journal_entry_id' => $entry->id,
            'action' => 'first_approve',
            'comment' => 'First approval',
        ]);
        $this->assertDatabaseHas('journal_approval_comments', [
            'journal_entry_id' => $entry->id,
            'action' => 'approve',
            'comment' => 'Second approval',
        ]);
    }

    public function test_reject_requires_reason(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $entry = JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'transaction_date' => now()->toDateString(),
            'status' => JournalEntry::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $this->actingAs($owner)
            ->post(route('journals.reject', ['journal' => $entry->id]), [])
            ->assertSessionHasErrors('reject_reason');
    }
}
