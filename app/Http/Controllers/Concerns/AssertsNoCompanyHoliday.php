<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Company;
use App\Models\CompanyHoliday;
use Illuminate\Validation\ValidationException;

trait AssertsNoCompanyHoliday
{
    protected function assertNoCompanyHoliday(int $companyId, string $date, string $attribute = 'transaction_date'): void
    {
        $company = Company::query()->find($companyId);
        if ($company === null || $company->isWorkingDay($date)) {
            return;
        }

        $holiday = CompanyHoliday::query()
            ->where('company_id', $companyId)
            ->whereDate('holiday_date', $date)
            ->first();

        if ($holiday !== null) {
            throw ValidationException::withMessages([
                $attribute => __(':date is a company holiday (:name). Posting is only allowed on working days.', [
                    'date' => $date,
                    'name' => $holiday->name !== null && $holiday->name !== ''
                        ? $holiday->name
                        : __('Holiday'),
                ]),
            ]);
        }

        throw ValidationException::withMessages([
            $attribute => __(
                ':date is not a working day (weekend). Mark it as a working day on Company → Holidays if you need to post.',
                ['date' => $date],
            ),
        ]);
    }
}
