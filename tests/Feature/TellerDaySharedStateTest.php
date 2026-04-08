<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TellerDaySharedStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_and_staff_share_same_teller_day_by_company_and_date(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        $staff = User::factory()->staff($company)->create();
        $date = '2026-04-08';

        $this->actingAs($owner)
            ->post(route('teller.day-close.start', absolute: false), [
                'close_date' => $date,
                'vault_opening_cash' => '500.00',
                'memo' => 'Open shared day',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->actingAs($staff)
            ->get(route('teller.day-close.create', ['date' => $date], absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Accounting/Teller/DayClose')
                ->where('selectedDate', $date)
                ->where('openDay.close_date', $date));

        $this->actingAs($staff)
            ->get(route('journals.create-cash-in', ['date' => $date], absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Accounting/Journals/CashEntryCreate')
                ->where('mode', 'in'));
    }
}

