<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\FinancialPosition;
use App\Models\FinancialPositionMovement;
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
        $showZero = $request->boolean('show_zero', true);

        $service = new AccountingReportService($company->id);
        $data = $service->trialBalance($asOf, $showZero);

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'report' => $data,
            'as_of' => $asOf->toDateString(),
            'show_zero' => $showZero,
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
        $showZero = $request->boolean('show_zero', true);

        $service = new AccountingReportService($company->id);
        $data = $service->profitAndLoss($from, $to, $showZero);

        return Inertia::render('Accounting/Reports/ProfitAndLoss', [
            'report' => $data,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'show_zero' => $showZero,
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
        $showZero = $request->boolean('show_zero', true);

        $service = new AccountingReportService($company->id);
        $data = $service->balanceSheet($asOf, $showZero);

        return Inertia::render('Accounting/Reports/BalanceSheet', [
            'report' => $data,
            'as_of' => $asOf->toDateString(),
            'show_zero' => $showZero,
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

    public function parAging(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);
        $today = Carbon::today();

        $loans = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', FinancialPosition::CATEGORY_LOAN)
            ->where('principal_cents', '>', 0)
            ->with(['member:id,member_number,name', 'loanProduct:id,product_code,name'])
            ->orderBy('title')
            ->get()
            ->filter(function (FinancialPosition $p) {
                if (! $p->usesStructuredLoanFlow()) {
                    return true;
                }

                return $p->isStructuredCatalogOperational();
            });

        $loanIds = $loans->pluck('id')->all();
        $lastInstallmentByPosition = $loanIds === []
            ? collect()
            : FinancialPositionMovement::query()
                ->whereIn('financial_position_id', $loanIds)
                ->where('type', FinancialPositionMovement::TYPE_INSTALLMENT)
                ->selectRaw('financial_position_id, max(created_at) as last_at')
                ->groupBy('financial_position_id')
                ->pluck('last_at', 'financial_position_id');

        $bucketTotals = [
            'current' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'days_over_90' => 0,
            'never_paid' => 0,
        ];
        $totalOutstanding = 0;
        $rows = [];

        foreach ($loans as $p) {
            $principal = (int) $p->principal_cents;
            $totalOutstanding += $principal;

            $lastAt = $lastInstallmentByPosition->get($p->id);
            if ($lastAt === null) {
                $anchor = $p->start_date
                    ? Carbon::parse($p->start_date)
                    : Carbon::parse($p->created_at);
                $daysSince = (int) $anchor->diffInDays($today);
                $bucket = 'never_paid';
            } else {
                $last = Carbon::parse($lastAt);
                $daysSince = (int) $last->diffInDays($today);
                if ($daysSince <= 30) {
                    $bucket = 'current';
                } elseif ($daysSince <= 60) {
                    $bucket = 'days_31_60';
                } elseif ($daysSince <= 90) {
                    $bucket = 'days_61_90';
                } else {
                    $bucket = 'days_over_90';
                }
            }

            $bucketTotals[$bucket] += $principal;

            $rows[] = [
                'id' => $p->id,
                'title' => $p->title,
                'account_number' => $p->account_number,
                'principal_cents' => $principal,
                'member_number' => $p->member?->member_number,
                'member_name' => $p->member?->name,
                'product_code' => $p->loanProduct?->product_code,
                'days_since_installment' => $lastAt !== null ? $daysSince : null,
                'days_since_start_or_open' => $lastAt === null ? $daysSince : null,
                'bucket' => $bucket,
            ];
        }

        $atRiskCents = $bucketTotals['days_31_60']
            + $bucketTotals['days_61_90']
            + $bucketTotals['days_over_90']
            + $bucketTotals['never_paid'];

        $parRatioBps = $totalOutstanding > 0
            ? (int) round(($atRiskCents / $totalOutstanding) * 10000)
            : 0;

        return Inertia::render('Accounting/Reports/ParAging', [
            'rows' => $rows,
            'summary' => [
                'total_outstanding_cents' => $totalOutstanding,
                'at_risk_cents' => $atRiskCents,
                'bucket_totals_cents' => $bucketTotals,
                'par_ratio_bps' => $parRatioBps,
            ],
            'as_of' => $today->toDateString(),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'letterhead' => $this->companyLetterhead($company),
        ]);
    }

    public function loanAccounts(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);

        $rows = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', FinancialPosition::CATEGORY_LOAN)
            ->with(['member:id,member_number,name', 'loanProduct:id,product_code,name'])
            ->orderBy('account_number')
            ->orderBy('id')
            ->get()
            ->map(fn (FinancialPosition $p) => [
                'id' => $p->id,
                'account_number' => $p->account_number,
                'title' => $p->title,
                'member_number' => $p->member?->member_number,
                'member_name' => $p->member?->name,
                'product_code' => $p->loanProduct?->product_code,
                'product_name' => $p->loanProduct?->name,
                'workflow_status' => $p->loan_workflow_status,
                'principal_cents' => (int) $p->principal_cents,
                'start_date' => $p->start_date?->toDateString(),
            ])
            ->values()
            ->all();

        return Inertia::render('Accounting/Reports/LoanAccounts', [
            'rows' => $rows,
            'summary' => [
                'accounts_total' => count($rows),
                'principal_total_cents' => (int) array_sum(array_map(
                    fn (array $r): int => (int) $r['principal_cents'],
                    $rows,
                )),
            ],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'letterhead' => $this->companyLetterhead($company),
        ]);
    }

    public function savingsAccounts(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);

        $rows = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', FinancialPosition::CATEGORY_SAVINGS)
            ->with(['member:id,member_number,name', 'savingsProduct:id,product_code,name'])
            ->orderBy('account_number')
            ->orderBy('id')
            ->get()
            ->map(fn (FinancialPosition $p) => [
                'id' => $p->id,
                'account_number' => $p->account_number,
                'title' => $p->title,
                'member_number' => $p->member?->member_number,
                'member_name' => $p->member?->name,
                'product_code' => $p->savingsProduct?->product_code,
                'product_name' => $p->savingsProduct?->name,
                'workflow_status' => $p->savings_workflow_status,
                'principal_cents' => (int) $p->principal_cents,
                'start_date' => $p->start_date?->toDateString(),
            ])
            ->values()
            ->all();

        return Inertia::render('Accounting/Reports/SavingsAccounts', [
            'rows' => $rows,
            'summary' => [
                'accounts_total' => count($rows),
                'principal_total_cents' => (int) array_sum(array_map(
                    fn (array $r): int => (int) $r['principal_cents'],
                    $rows,
                )),
            ],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'letterhead' => $this->companyLetterhead($company),
        ]);
    }
}
