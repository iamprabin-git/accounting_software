<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\ResolvesCompanyForCompanyWebRoutes;
use App\Http\Controllers\Controller;
use App\Models\CompanyWorkingDayOverride;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CompanyWorkingDayOverrideController extends Controller
{
    use ResolvesCompanyForCompanyWebRoutes;

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canManageCompanyWebSettings(), 403);

        $company = $this->companyForCompanyWebRoutes($request);

        $validated = $request->validate([
            'work_date' => ['required', 'date'],
        ]);

        $date = (string) $validated['work_date'];

        $company->holidays()->whereDate('holiday_date', $date)->delete();

        $d = Carbon::parse($date)->startOfDay();
        if ($d->isWeekend()) {
            $company->workingDayOverrides()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'work_date' => $date,
                ],
                [],
            );
        }

        return redirect()
            ->route(
                'company.holidays.index',
                $this->companyIdQueryForRedirect($request, $company),
            )
            ->with('status', __('Working day saved.'));
    }

    public function destroy(Request $request, CompanyWorkingDayOverride $override): RedirectResponse
    {
        abort_unless($request->user()?->canManageCompanyWebSettings(), 403);

        $company = $this->companyForCompanyWebRoutes($request);
        abort_unless((int) $override->company_id === (int) $company->id, 404);

        $override->delete();

        return redirect()
            ->route(
                'company.holidays.index',
                $this->companyIdQueryForRedirect($request, $company),
            )
            ->with('status', __('Weekend working override removed.'));
    }
}
