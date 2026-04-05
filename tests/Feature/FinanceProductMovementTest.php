<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FinancialPosition;
use App\Models\FinancialPositionMovement;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceProductMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_increases_principal_and_records_movement(): void
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

        $position = FinancialPosition::query()->create([
            'company_id' => $company->id,
            'member_id' => $member->id,
            'category' => FinancialPosition::CATEGORY_SAVINGS,
            'title' => 'Saver',
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
                ['amount' => '50.00', 'memo' => 'Top-up'],
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
    }
}
