<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\FinancialPosition;
use App\Models\Member;
use App\Models\MemberGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberGroupDepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_deposit_batch_posts_member_savings_journal_and_movement(): void
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

        $member = Member::query()->create([
            'company_id' => $company->id,
            'member_number' => 1,
            'name' => 'Member One',
            'status' => Member::STATUS_APPROVED,
            'created_by_user_id' => $owner->id,
            'approved_by_user_id' => $owner->id,
            'approved_at' => now(),
        ]);

        $savings = FinancialPosition::query()->create([
            'company_id' => $company->id,
            'member_id' => $member->id,
            'category' => FinancialPosition::CATEGORY_SAVINGS,
            'title' => 'Savings A',
            'principal_cents' => 1000,
            'annual_interest_rate_percent' => 0,
            'account_number' => 'SV-0001',
        ]);

        $create = $this->actingAs($owner)->post(route('member-groups.store'), [
            'name' => 'Center A',
            'member_ids' => [$member->id],
        ]);
        $create->assertSessionHasNoErrors()->assertRedirect();

        $groupId = (int) MemberGroup::query()->value('id');
        $this->assertGreaterThan(0, $groupId);

        $this->actingAs($owner)->post(route('member-groups.deposit-batches.store', ['group' => $groupId]), [
            'transaction_date' => now()->toDateString(),
            'debit_chart_account_id' => $cash->id,
            'reference' => 'GRP-1',
            'lines' => [
                [
                    'member_id' => $member->id,
                    'financial_position_id' => $savings->id,
                    'amount' => 25.50,
                ],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $savings->refresh();
        $this->assertSame(3550, (int) $savings->principal_cents);

        $this->assertDatabaseHas('group_deposit_batches', [
            'company_id' => $company->id,
            'member_group_id' => $groupId,
            'line_count' => 1,
            'total_cents' => 2550,
        ]);
        $this->assertDatabaseHas('group_deposit_batch_lines', [
            'member_id' => $member->id,
            'financial_position_id' => $savings->id,
            'amount_cents' => 2550,
        ]);
        $this->assertDatabaseHas('financial_position_movements', [
            'financial_position_id' => $savings->id,
            'type' => 'deposit',
            'amount_cents' => 2550,
        ]);
        $this->assertDatabaseHas('accounting_audit_logs', [
            'company_id' => $company->id,
            'action' => 'group_deposit.posted',
        ]);
    }
}
