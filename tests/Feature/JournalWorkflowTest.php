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
}
