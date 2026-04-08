<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\TellerDayClose;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalCashEntryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: ChartAccount, 1: ChartAccount, 2: ChartAccount}
     */
    private function accounts(Company $company, User $owner): array
    {
        $cash = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1000',
            'name' => 'Cash in hand',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $sales = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '4000',
            'name' => 'Sales',
            'type' => ChartAccount::TYPE_REVENUE,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $expense = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '5000',
            'name' => 'Office expense',
            'type' => ChartAccount::TYPE_EXPENSE,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        return [$cash, $sales, $expense];
    }

    private function openTellerDay(Company $company, User $owner, string $date): void
    {
        TellerDayClose::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'close_date' => $date,
            'day_status' => TellerDayClose::STATUS_OPEN,
            'opening_cash_cents' => 0,
            'counted_cash_cents' => 0,
            'expected_cash_cents' => 0,
            'system_cash_cents' => 0,
            'cash_received_cents' => 0,
            'closing_error_cents' => 0,
            'started_at' => now(),
        ]);
    }

    public function test_cash_in_debits_cash_and_credits_counterparts(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        [$cash, $sales] = $this->accounts($company, $owner);
        $this->openTellerDay($company, $owner, '2026-04-04');

        $this->actingAs($owner)->post(route('journals.store-cash-in', absolute: false), [
            'transaction_date' => '2026-04-04',
            'cash_chart_account_id' => $cash->id,
            'lines' => [
                ['chart_account_id' => $sales->id, 'amount' => '100.00', 'description' => 'Counter sale'],
                ['chart_account_id' => $sales->id, 'amount' => '50.00', 'description' => 'Second line ok'],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $entry = JournalEntry::query()->latest('id')->firstOrFail();
        $this->assertTrue($entry->isBalanced());
        $this->assertSame(150_00, (int) $entry->lines->where('chart_account_id', $cash->id)->sum('debit_cents'));
        $this->assertSame(150_00, (int) $entry->lines->sum('credit_cents'));
    }

    public function test_cash_out_credits_cash_and_debits_counterparts(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        [$cash, , $expense] = $this->accounts($company, $owner);
        $this->openTellerDay($company, $owner, '2026-04-04');

        $this->actingAs($owner)->post(route('journals.store-cash-out', absolute: false), [
            'transaction_date' => '2026-04-04',
            'cash_chart_account_id' => $cash->id,
            'lines' => [
                ['chart_account_id' => $expense->id, 'amount' => '25.50'],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $entry = JournalEntry::query()->latest('id')->firstOrFail();
        $this->assertTrue($entry->isBalanced());
        $this->assertSame(25_50, (int) $entry->lines->where('chart_account_id', $cash->id)->sum('credit_cents'));
        $this->assertSame(25_50, (int) $entry->lines->where('chart_account_id', $expense->id)->sum('debit_cents'));
    }

    public function test_cash_in_rejects_counterpart_same_as_cash(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        [$cash] = $this->accounts($company, $owner);
        $this->openTellerDay($company, $owner, '2026-04-04');

        $this->actingAs($owner)->post(route('journals.store-cash-in', absolute: false), [
            'transaction_date' => '2026-04-04',
            'cash_chart_account_id' => $cash->id,
            'lines' => [
                ['chart_account_id' => $cash->id, 'amount' => '10.00'],
            ],
        ])->assertSessionHasErrors('lines.0.chart_account_id');
    }
}
