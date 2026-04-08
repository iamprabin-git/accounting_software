<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\TellerDayClose;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    use ResolvesAccountingCompany;

    public function frontDesk(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user && $user->canEditAccounting(), 403);

        $company = $this->accountingCompany($request);
        $tellerDayOpenToday = TellerDayClose::query()
            ->where('company_id', $company->id)
            ->where('day_status', TellerDayClose::STATUS_OPEN)
            ->exists();

        return Inertia::render('Workspace/FrontDesk', [
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'tellerDayOpenToday' => $tellerDayOpenToday,
        ]);
    }

    public function backOffice(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user && $user->canEditAccounting(), 403);

        $company = $this->accountingCompany($request);

        return Inertia::render('Workspace/BackOffice', [
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }
}
