<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\ChartAccount;
use App\Models\JournalEntry;
use App\Services\AccountingReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountingReportController extends Controller
{
    use ResolvesAccountingCompany;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);

        return Inertia::render('Accounting/Reports/Index', [
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'letterhead' => $this->companyLetterhead($company),
        ]);
    }

    public function trialBalance(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);
        $asOf = Carbon::parse($request->input('as_of') ?: now()->toDateString());

        $service = new AccountingReportService($company->id);
        $data = $service->trialBalance($asOf);

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'report' => $data,
            'as_of' => $asOf->toDateString(),
            'printMode' => $request->boolean('print'),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'letterhead' => $this->companyLetterhead($company),
        ]);
    }

    public function profitAndLoss(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);
        $from = Carbon::parse($request->input('from') ?: now()->startOfMonth()->toDateString());
        $to = Carbon::parse($request->input('to') ?: now()->toDateString());

        $service = new AccountingReportService($company->id);
        $data = $service->profitAndLoss($from, $to);

        return Inertia::render('Accounting/Reports/ProfitAndLoss', [
            'report' => $data,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'printMode' => $request->boolean('print'),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'letterhead' => $this->companyLetterhead($company),
        ]);
    }

    public function balanceSheet(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);
        $asOf = Carbon::parse($request->input('as_of') ?: now()->toDateString());

        $service = new AccountingReportService($company->id);
        $data = $service->balanceSheet($asOf);

        return Inertia::render('Accounting/Reports/BalanceSheet', [
            'report' => $data,
            'as_of' => $asOf->toDateString(),
            'printMode' => $request->boolean('print'),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'letterhead' => $this->companyLetterhead($company),
        ]);
    }

    public function cashFlow(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);
        $from = Carbon::parse($request->input('from') ?: now()->startOfMonth()->toDateString());
        $to = Carbon::parse($request->input('to') ?: now()->toDateString());

        $service = new AccountingReportService($company->id);
        $data = $service->cashFlow($from, $to);

        return Inertia::render('Accounting/Reports/CashFlow', [
            'report' => $data,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'printMode' => $request->boolean('print'),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'letterhead' => $this->companyLetterhead($company),
        ]);
    }

    public function generalLedger(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);
        $from = Carbon::parse($request->input('from') ?: now()->startOfMonth()->toDateString());
        $to = Carbon::parse($request->input('to') ?: now()->toDateString());
        $accountId = (int) $request->input('account_id', 0);

        $accounts = ChartAccount::query()
            ->where('company_id', $company->id)
            ->approvedForJournals()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (ChartAccount $a) => [
                'id' => $a->id,
                'label' => $a->code.' — '.$a->name,
            ])
            ->all();

        if ($accountId === 0 && count($accounts) > 0) {
            $accountId = (int) $accounts[0]['id'];
        }

        $service = new AccountingReportService($company->id);
        $data = $accountId > 0 ? $service->generalLedger($accountId, $from, $to) : [
            'account' => null,
            'opening_balance_cents' => 0,
            'lines' => [],
        ];

        return Inertia::render('Accounting/Reports/GeneralLedger', [
            'report' => $data,
            'accounts' => $accounts,
            'account_id' => $accountId,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'printMode' => $request->boolean('print'),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'letterhead' => $this->companyLetterhead($company),
        ]);
    }
}
