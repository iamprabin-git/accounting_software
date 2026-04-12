<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyHoliday;
use App\Models\CompanyWorkingDayOverride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyHolidayTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_view_holiday_calendar_and_crud(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->get(route('company.holidays.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Company/Holidays/Index'));

        $this->actingAs($owner)
            ->post(route('company.holidays.store', absolute: false), [
                'holiday_date' => '2026-12-25',
                'name' => 'Christmas',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('company.holidays.index', absolute: false));

        $row = CompanyHoliday::query()->where('company_id', $company->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('Christmas', $row->name);

        $this->actingAs($owner)
            ->delete(route('company.holidays.destroy', ['holiday' => $row->id], absolute: false))
            ->assertRedirect(route('company.holidays.index', absolute: false));

        $this->assertSame(0, CompanyHoliday::query()->where('company_id', $company->id)->count());
    }

    public function test_staff_can_view_holidays_but_cannot_modify(): void
    {
        $company = Company::factory()->create();
        $staff = User::factory()->staff($company)->create();

        $this->actingAs($staff)
            ->get(route('company.holidays.index', absolute: false))
            ->assertOk();

        $this->actingAs($staff)
            ->post(route('company.holidays.store', absolute: false), [
                'holiday_date' => '2026-01-01',
                'name' => 'X',
            ])
            ->assertForbidden();

        $this->assertSame(0, CompanyHoliday::query()->where('company_id', $company->id)->count());
    }

    public function test_setting_holiday_removes_weekend_working_override(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        CompanyWorkingDayOverride::query()->create([
            'company_id' => $company->id,
            'work_date' => '2026-06-06',
        ]);

        $this->actingAs($owner)
            ->post(route('company.holidays.store', absolute: false), [
                'holiday_date' => '2026-06-06',
                'name' => 'Special',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, CompanyWorkingDayOverride::query()->where('company_id', $company->id)->count());
        $this->assertTrue(CompanyHoliday::query()->where('company_id', $company->id)->exists());
    }

    public function test_working_day_override_clears_holiday_on_weekday(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        CompanyHoliday::query()->create([
            'company_id' => $company->id,
            'holiday_date' => '2026-06-10',
            'name' => 'Off',
        ]);

        $this->actingAs($owner)
            ->post(route('company.working-day-overrides.store', absolute: false), [
                'work_date' => '2026-06-10',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, CompanyHoliday::query()->where('company_id', $company->id)->count());
    }

    public function test_owner_can_add_and_remove_weekend_override(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->post(route('company.working-day-overrides.store', absolute: false), [
                'work_date' => '2026-06-07',
            ])
            ->assertRedirect(route('company.holidays.index', absolute: false));

        $row = CompanyWorkingDayOverride::query()->where('company_id', $company->id)->first();
        $this->assertNotNull($row);

        $this->actingAs($owner)
            ->delete(route('company.working-day-overrides.destroy', ['override' => $row->id], absolute: false))
            ->assertRedirect(route('company.holidays.index', absolute: false));

        $this->assertSame(0, CompanyWorkingDayOverride::query()->where('company_id', $company->id)->count());
    }

    public function test_staff_cannot_modify_working_day_overrides(): void
    {
        $company = Company::factory()->create();
        $staff = User::factory()->staff($company)->create();

        $this->actingAs($staff)
            ->post(route('company.working-day-overrides.store', absolute: false), [
                'work_date' => '2026-06-07',
            ])
            ->assertForbidden();

        $this->assertSame(0, CompanyWorkingDayOverride::query()->where('company_id', $company->id)->count());
    }
}
