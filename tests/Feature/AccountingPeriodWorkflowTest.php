<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingPeriodWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_close_and_reopen_with_reason(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->post(route('company.period.close'), [
                'close_lock_date' => '2026-03-31',
                'close_reason' => 'Month-end close after reconciliation.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $company->refresh();
        $this->assertSame('2026-03-31', $company->journal_lock_date?->toDateString());
        $this->assertSame('Month-end close after reconciliation.', $company->journal_lock_reason);
        $this->assertSame($owner->id, $company->journal_lock_updated_by_user_id);
        $this->assertNotNull($company->journal_lock_updated_at);

        $this->assertDatabaseHas('accounting_audit_logs', [
            'company_id' => $company->id,
            'action' => 'period.closed',
            'user_id' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->post(route('company.period.reopen'), [
                'reopen_to_date' => '',
                'reopen_reason' => 'Reopening for late bank adjustment.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $company->refresh();
        $this->assertNull($company->journal_lock_date);
        $this->assertSame('Reopening for late bank adjustment.', $company->journal_lock_reason);
        $this->assertSame($owner->id, $company->journal_lock_updated_by_user_id);

        $this->assertDatabaseHas('accounting_audit_logs', [
            'company_id' => $company->id,
            'action' => 'period.reopened',
            'user_id' => $owner->id,
        ]);
    }

    public function test_staff_cannot_close_period(): void
    {
        $company = Company::factory()->create();
        $staff = User::factory()->staff($company)->create();

        $this->actingAs($staff)
            ->post(route('company.period.close'), [
                'close_lock_date' => '2026-03-31',
                'close_reason' => 'Unauthorized attempt.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('accounting_audit_logs', [
            'company_id' => $company->id,
            'action' => 'period.closed',
        ]);
    }

    public function test_close_is_blocked_when_checklist_has_draft_or_pending_journals(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'transaction_date' => '2026-03-15',
            'status' => JournalEntry::STATUS_DRAFT,
        ]);
        JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'transaction_date' => '2026-03-20',
            'status' => JournalEntry::STATUS_PENDING,
            'submitted_at' => now(),
        ]);
        JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'transaction_date' => '2026-03-25',
            'status' => JournalEntry::STATUS_REJECTED,
        ]);
        JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'transaction_date' => '2026-04-02',
            'status' => JournalEntry::STATUS_DRAFT,
        ]);

        $this->actingAs($owner)
            ->post(route('company.period.close'), [
                'close_lock_date' => '2026-03-31',
                'close_reason' => 'Month-end close attempt.',
            ])
            ->assertSessionHasErrors('close_checklist');

        $company->refresh();
        $this->assertNull($company->journal_lock_date);
    }

    public function test_close_is_blocked_when_checklist_has_rejected_journals(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'transaction_date' => '2026-03-10',
            'status' => JournalEntry::STATUS_REJECTED,
        ]);

        $this->actingAs($owner)
            ->post(route('company.period.close'), [
                'close_lock_date' => '2026-03-31',
                'close_reason' => 'Close attempt with rejected journal present.',
            ])
            ->assertSessionHasErrors('close_checklist');

        $company->refresh();
        $this->assertNull($company->journal_lock_date);
    }

    public function test_month_end_close_rejects_non_last_day_of_nepali_month(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->post(route('company.period.close'), [
                'close_lock_date' => '2026-03-31',
                'close_reason' => 'Attempt Gregorian month end only.',
                'close_type' => 'month_end',
            ])
            ->assertSessionHasErrors('close_lock_date');

        $company->refresh();
        $this->assertNull($company->journal_lock_date);
    }

    public function test_month_end_close_accepts_last_day_of_nepali_month(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->post(route('company.period.close'), [
                'close_lock_date' => '2026-04-13',
                'close_reason' => 'Chaitra month end (BS).',
                'close_type' => 'month_end',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $company->refresh();
        $this->assertSame('2026-04-13', $company->journal_lock_date?->toDateString());
    }
}
