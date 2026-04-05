<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_create_and_list_chart_accounts(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->get(route('chart-accounts.index', absolute: false))
            ->assertOk();

        $this->actingAs($owner)
            ->post(route('chart-accounts.store', absolute: false), [
                'code' => '1999',
                'name' => 'Petty cash',
                'type' => ChartAccount::TYPE_ASSET,
                'description' => 'Test',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('chart_accounts', [
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1999',
            'name' => 'Petty cash',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_by_user_id' => $owner->id,
        ]);
    }

    public function test_staff_can_list_and_create_pending_chart_accounts_but_cannot_edit(): void
    {
        $company = Company::factory()->create();
        $staff = User::factory()->staff($company)->create();

        $this->actingAs($staff)
            ->get(route('chart-accounts.index', absolute: false))
            ->assertOk();

        $this->actingAs($staff)
            ->post(route('chart-accounts.store', absolute: false), [
                'code' => '2999',
                'name' => 'Staff proposed',
                'type' => ChartAccount::TYPE_EXPENSE,
                'description' => null,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('chart_accounts', [
            'company_id' => $company->id,
            'user_id' => $staff->id,
            'code' => '2999',
            'approval_status' => ChartAccount::STATUS_PENDING,
            'approved_by_user_id' => null,
        ]);

        $pendingId = ChartAccount::query()->where('code', '2999')->value('id');

        $this->actingAs($staff)
            ->get(route('chart-accounts.edit', ['account' => $pendingId], absolute: false))
            ->assertForbidden();
    }

    public function test_company_owner_can_approve_pending_chart_account(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        $staff = User::factory()->staff($company)->create();

        $account = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $staff->id,
            'code' => '3999',
            'name' => 'Needs approval',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_PENDING,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ]);

        $this->actingAs($owner)
            ->post(route('chart-accounts.approve', ['account' => $account->id], absolute: false))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $account->refresh();

        $this->assertSame(ChartAccount::STATUS_APPROVED, $account->approval_status);
        $this->assertNotNull($account->approved_at);
        $this->assertSame($owner->id, $account->approved_by_user_id);
    }

    public function test_company_cannot_delete_account_used_on_journal_lines(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $account = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => ChartAccount::TYPE_ASSET,
        ]);

        $other = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '3000',
            'name' => 'Equity',
            'type' => ChartAccount::TYPE_EQUITY,
        ]);

        $entry = JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'reference' => null,
            'memo' => null,
            'transaction_date' => now()->toDateString(),
            'status' => JournalEntry::STATUS_DRAFT,
        ]);

        $entry->lines()->create([
            'chart_account_id' => $account->id,
            'debit_cents' => 100,
            'credit_cents' => 0,
            'description' => null,
        ]);
        $entry->lines()->create([
            'chart_account_id' => $other->id,
            'debit_cents' => 0,
            'credit_cents' => 100,
            'description' => null,
        ]);

        $this->actingAs($owner)
            ->delete(route('chart-accounts.destroy', ['account' => $account->id], absolute: false))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('chart_accounts', ['id' => $account->id]);
    }
}
