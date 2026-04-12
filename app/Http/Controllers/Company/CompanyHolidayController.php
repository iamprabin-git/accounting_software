<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\ResolvesCompanyForCompanyWebRoutes;
use App\Http\Controllers\Controller;
use App\Models\CompanyHoliday;
use App\Models\CompanyWorkingDayOverride;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyHolidayController extends Controller
{
    use ResolvesCompanyForCompanyWebRoutes;

    public function index(Request $request): Response
    {
        $company = $this->companyForCompanyWebRoutes($request);

        $holidays = $company->holidays()
            ->orderBy('holiday_date')
            ->get(['id', 'holiday_date', 'name']);

        $workingOverrides = $company->workingDayOverrides()
            ->orderBy('work_date')
            ->get(['id', 'work_date']);

        return Inertia::render('Company/Holidays/Index', [
            'holidays' => $holidays->map(fn (CompanyHoliday $h) => [
                'id' => $h->id,
                'holiday_date' => $h->holiday_date->toDateString(),
                'name' => $h->name ?? '',
            ])->values()->all(),
            'working_overrides' => $workingOverrides->map(fn ($o) => [
                'id' => $o->id,
                'work_date' => $o->work_date->toDateString(),
            ])->values()->all(),
            'can_manage_holidays' => $request->user()?->canManageCompanyWebSettings() ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canManageCompanyWebSettings(), 403);

        $company = $this->companyForCompanyWebRoutes($request);

        $validated = $request->validate([
            'holiday_date' => ['required', 'date'],
            'name' => ['nullable', 'string', 'max:160'],
        ]);

        $date = (string) $validated['holiday_date'];

        CompanyWorkingDayOverride::query()
            ->where('company_id', $company->id)
            ->whereDate('work_date', $date)
            ->delete();

        $company->holidays()->updateOrCreate(
            [
                'company_id' => $company->id,
                'holiday_date' => $date,
            ],
            [
                'name' => $validated['name'] ?? null,
            ],
        );

        return redirect()
            ->route(
                'company.holidays.index',
                $this->companyIdQueryForRedirect($request, $company),
            )
            ->with('status', __('Holiday saved.'));
    }

    public function destroy(Request $request, CompanyHoliday $holiday): RedirectResponse
    {
        abort_unless($request->user()?->canManageCompanyWebSettings(), 403);

        $company = $this->companyForCompanyWebRoutes($request);
        abort_unless((int) $holiday->company_id === (int) $company->id, 404);

        $holiday->delete();

        return redirect()
            ->route(
                'company.holidays.index',
                $this->companyIdQueryForRedirect($request, $company),
            )
            ->with('status', __('Holiday removed.'));
    }
}
