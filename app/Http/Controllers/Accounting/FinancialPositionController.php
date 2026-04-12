<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\FinancialPosition;
use App\Models\FinancialPositionAccrual;
use App\Models\FinancialPositionMovement;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\SavingsProduct;
use App\Models\User;
use App\Services\FinanceAccrualService;
use App\Services\FinanceJournalPostingService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class FinancialPositionController extends Controller
{
    use ResolvesAccountingCompany;

    public function accountEntry(Request $request): Response
    {
        $this->authorize('viewAny', FinancialPosition::class);

        $company = $this->accountingCompany($request);

        $category = $request->query('category', FinancialPosition::CATEGORY_SAVINGS);
        if (! in_array($category, [FinancialPosition::CATEGORY_LOAN, FinancialPosition::CATEGORY_SAVINGS], true)) {
            $category = FinancialPosition::CATEGORY_SAVINGS;
        }

        $rawAcct = $request->query('account_number', '');
        $accountNumber = is_string($rawAcct) ? trim($rawAcct) : '';

        $resolved = null;
        $lookupAttempted = $accountNumber !== '';

        if ($lookupAttempted) {
            $position = FinancialPosition::query()
                ->where('company_id', $company->id)
                ->where('category', $category)
                ->where('account_number', $accountNumber)
                ->with('member:id,name,member_number,status')
                ->first();

            if ($position && $position->usesStructuredCatalogWorkflow() && ! $position->isStructuredCatalogOperational()) {
                $position = null;
            }

            if ($position) {
                $resolved = [
                    'id' => $position->id,
                    'title' => $position->title,
                    'account_number' => $position->account_number,
                    'principal_cents' => (int) $position->principal_cents,
                    'member_name' => $position->member?->name,
                    'member_number' => $position->member?->member_number,
                    'member_status' => $position->member?->status,
                    'uses_structured_loan' => $position->usesStructuredLoanFlow(),
                    'loan_operational' => $position->isLoanOperational(),
                    'uses_structured_savings' => $position->usesStructuredSavingsFlow(),
                    'savings_operational' => $position->isSavingsOperational(),
                ];
            }
        }

        return Inertia::render('Accounting/Finance/AccountEntry', [
            'category' => $category,
            'account_number_query' => $accountNumber,
            'lookup_attempted' => $lookupAttempted,
            'resolved' => $resolved,
            'modal_chart_accounts' => in_array($category, [
                FinancialPosition::CATEGORY_LOAN,
                FinancialPosition::CATEGORY_SAVINGS,
            ], true)
                ? $this->chartAccountOptions($company->id)
                : [],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function index(Request $request, string $category): Response
    {
        $this->authorize('viewAny', FinancialPosition::class);

        $category = $this->validatedCategory($category);

        $company = $this->accountingCompany($request);

        $base = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', $category);

        $totalPrincipalCents = (clone $base)->sum('principal_cents');
        $totalAnnualInterestCents = (int) (clone $base)->orderBy('title')->get()
            ->sum(fn (FinancialPosition $p) => $p->annualInterestCents());

        $positions = (clone $base)
            ->with([
                'member:id,name,status,member_number',
                'loanProduct:id,product_code,name',
                'savingsProduct:id,product_code,name',
            ])
            ->withCount(['movements', 'accruals'])
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString()
            ->through(function (FinancialPosition $p) {
                $hasTransactions = ((int) ($p->movements_count ?? 0) > 0)
                    || ((int) ($p->accruals_count ?? 0) > 0);

                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'member_id' => $p->member_id,
                    'member_name' => $p->member?->name,
                    'member_number' => $p->member?->member_number,
                    'member_status' => $p->member?->status,
                    'account_number' => $p->account_number,
                    'principal_cents' => (int) $p->principal_cents,
                    'annual_interest_rate_percent' => (string) $p->annual_interest_rate_percent,
                    'start_date' => $p->start_date?->toDateString(),
                    'notes' => $p->notes,
                    'annual_interest_cents' => $p->annualInterestCents(),
                    'monthly_interest_cents' => $p->monthlyInterestCents(),
                    'uses_structured_loan' => $p->usesStructuredLoanFlow(),
                    'loan_workflow_status' => $p->loan_workflow_status,
                    'uses_structured_savings' => $p->usesStructuredSavingsFlow(),
                    'savings_workflow_status' => $p->savings_workflow_status,
                    'proposed_account_number' => $p->proposedCatalogAccountNumber(),
                    'loan_operational' => $p->isLoanOperational(),
                    'savings_operational' => $p->isSavingsOperational(),
                    'sanctioned_amount_cents' => $p->sanctioned_amount_cents !== null ? (int) $p->sanctioned_amount_cents : null,
                    'loan_product_label' => $p->loanProduct
                        ? $p->loanProduct->product_code.' — '.$p->loanProduct->name
                        : null,
                    'savings_product_label' => $p->savingsProduct
                        ? $p->savingsProduct->product_code.' — '.$p->savingsProduct->name
                        : null,
                    'can_delete' => ! $hasTransactions,
                ];
            });

        $workspace = $request->query('workspace', 'full');
        if (! in_array($workspace, ['full', 'front', 'back'], true)) {
            $workspace = 'full';
        }

        $prefill = [
            'title' => (string) ($request->query('title', '')),
            'member_id' => (string) ($request->query('member_id', '')),
            'loan_product_id' => (string) ($request->query('loan_product_id', '')),
            'savings_product_id' => (string) ($request->query('savings_product_id', '')),
            'annual_interest_rate_percent' => (string) ($request->query('annual_interest_rate_percent', '0')),
            'start_date' => (string) ($request->query('start_date', '')),
        ];

        return Inertia::render('Accounting/Finance/Index', [
            'category' => $category,
            'categoryLabel' => $this->categoryLabel($category),
            'workspace' => $workspace,
            'positions' => $positions,
            'totals' => [
                'principal_cents' => (int) $totalPrincipalCents,
                'annual_interest_cents' => $totalAnnualInterestCents,
            ],
            'modal_chart_accounts' => in_array($category, [
                FinancialPosition::CATEGORY_LOAN,
                FinancialPosition::CATEGORY_SAVINGS,
            ], true)
                ? $this->chartAccountOptions($company->id)
                : [],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function create(Request $request, string $category): Response
    {
        $this->authorize('create', FinancialPosition::class);

        $category = $this->validatedCategory($category);

        $company = $this->accountingCompany($request);

        $workspace = $request->query('workspace', 'full');
        if (! in_array($workspace, ['full', 'front', 'back'], true)) {
            $workspace = 'full';
        }

        $prefill = [
            'title' => (string) ($request->query('title', '')),
            'member_id' => (string) ($request->query('member_id', '')),
            'loan_product_id' => (string) ($request->query('loan_product_id', '')),
            'savings_product_id' => (string) ($request->query('savings_product_id', '')),
            'annual_interest_rate_percent' => (string) ($request->query('annual_interest_rate_percent', '0')),
            'start_date' => (string) ($request->query('start_date', '')),
        ];

        $loanProducts = [];
        if ($category === FinancialPosition::CATEGORY_LOAN) {
            $loanProducts = LoanProduct::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->orderBy('product_code')
                ->get(['id', 'product_code', 'name', 'default_annual_interest_rate_percent'])
                ->map(fn (LoanProduct $lp) => [
                    'id' => $lp->id,
                    'product_code' => $lp->product_code,
                    'name' => $lp->name,
                    'default_annual_interest_rate_percent' => (string) $lp->default_annual_interest_rate_percent,
                ])
                ->all();
        }

        $savingsProducts = [];
        if ($category === FinancialPosition::CATEGORY_SAVINGS) {
            $savingsProducts = SavingsProduct::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->orderBy('product_code')
                ->get(['id', 'product_code', 'name', 'default_annual_interest_rate_percent'])
                ->map(fn (SavingsProduct $sp) => [
                    'id' => $sp->id,
                    'product_code' => $sp->product_code,
                    'name' => $sp->name,
                    'default_annual_interest_rate_percent' => (string) $sp->default_annual_interest_rate_percent,
                ])
                ->all();
        }

        return Inertia::render('Accounting/Finance/Create', [
            'category' => $category,
            'categoryLabel' => $this->categoryLabel($category),
            'workspace' => $workspace,
            'approved_members' => $this->approvedMembersForSelect($company->id, $category),
            'loan_products' => $loanProducts,
            'savings_products' => $savingsProducts,
            'initial_form' => $prefill,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function store(Request $request, string $category): RedirectResponse
    {
        $this->authorize('create', FinancialPosition::class);

        $category = $this->validatedCategory($category);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $validated = $this->validatedPayload($request, $category, $company->id);

        $structuredLoan = $category === FinancialPosition::CATEGORY_LOAN
            && ! empty($validated['loan_product_id']);

        if ($structuredLoan) {
            $principalCents = (int) round(((float) $validated['principal']) * 100);
            if ($principalCents !== 0) {
                throw ValidationException::withMessages([
                    'principal' => __('Product-based loan applications start at zero until the loan is disbursed.'),
                ]);
            }

            $product = LoanProduct::query()
                ->where('company_id', $company->id)
                ->whereKey((int) $validated['loan_product_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $rate = $validated['annual_interest_rate_percent'] ?? $product->default_annual_interest_rate_percent;
            $sanctionedCents = isset($validated['sanctioned_amount'])
                ? (int) round(((float) $validated['sanctioned_amount']) * 100)
                : null;

            DB::transaction(function () use ($company, $validated, $product, $sanctionedCents, $rate) {
                LoanProduct::query()->whereKey($product->id)->lockForUpdate()->first();

                $seq = (int) FinancialPosition::query()
                    ->where('loan_product_id', $product->id)
                    ->lockForUpdate()
                    ->max('product_account_sequence') + 1;

                FinancialPosition::query()->create([
                    'company_id' => $company->id,
                    'member_id' => $validated['member_id'] ?? null,
                    'loan_product_id' => $product->id,
                    'loan_workflow_status' => FinancialPosition::LOAN_WORKFLOW_PENDING_APPROVAL,
                    'sanctioned_amount_cents' => $sanctionedCents,
                    'product_account_sequence' => $seq,
                    'account_number' => null,
                    'category' => FinancialPosition::CATEGORY_LOAN,
                    'title' => $validated['title'],
                    'principal_cents' => 0,
                    'annual_interest_rate_percent' => $rate,
                    'start_date' => $validated['start_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            });

            return redirect()->route('finance.positions.index', array_merge(
                ['category' => $category],
                $this->financeIndexQuery($request),
            ))->with('status', __('Loan application saved — pending back office approval.'));
        }

        $structuredSavings = $category === FinancialPosition::CATEGORY_SAVINGS
            && ! empty($validated['savings_product_id']);

        if ($structuredSavings) {
            $principalCents = (int) round(((float) $validated['principal']) * 100);
            if ($principalCents !== 0) {
                throw ValidationException::withMessages([
                    'principal' => __('Product-based savings applications start at zero until the first deposit is posted with a journal entry.'),
                ]);
            }

            $product = SavingsProduct::query()
                ->where('company_id', $company->id)
                ->whereKey((int) $validated['savings_product_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $rate = $validated['annual_interest_rate_percent'] ?? $product->default_annual_interest_rate_percent;

            DB::transaction(function () use ($company, $validated, $product, $rate) {
                SavingsProduct::query()->whereKey($product->id)->lockForUpdate()->first();

                $seq = (int) FinancialPosition::query()
                    ->where('savings_product_id', $product->id)
                    ->lockForUpdate()
                    ->max('savings_product_account_sequence') + 1;

                FinancialPosition::query()->create([
                    'company_id' => $company->id,
                    'member_id' => $validated['member_id'] ?? null,
                    'savings_product_id' => $product->id,
                    'savings_workflow_status' => FinancialPosition::LOAN_WORKFLOW_PENDING_APPROVAL,
                    'savings_product_account_sequence' => $seq,
                    'account_number' => null,
                    'category' => FinancialPosition::CATEGORY_SAVINGS,
                    'title' => $validated['title'],
                    'principal_cents' => 0,
                    'annual_interest_rate_percent' => $rate,
                    'start_date' => $validated['start_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            });

            return redirect()->route('finance.positions.index', array_merge(
                ['category' => $category],
                $this->financeIndexQuery($request),
            ))->with('status', __('Savings application saved — pending back office approval.'));
        }

        $principalCents = (int) round(((float) $validated['principal']) * 100);

        DB::transaction(function () use ($request, $company, $category, $validated, $principalCents) {
            $acct = null;
            if (in_array($category, [FinancialPosition::CATEGORY_LOAN, FinancialPosition::CATEGORY_SAVINGS], true)) {
                $raw = $validated['account_number'] ?? null;
                $acct = is_string($raw) ? trim($raw) : null;
                if ($acct === '') {
                    $acct = null;
                }
            }

            $position = FinancialPosition::query()->create([
                'company_id' => $company->id,
                'member_id' => $validated['member_id'] ?? null,
                'account_number' => $acct,
                'category' => $category,
                'title' => $validated['title'],
                'principal_cents' => $principalCents,
                'annual_interest_rate_percent' => $validated['annual_interest_rate_percent'] ?? 0,
                'start_date' => $validated['start_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (in_array($category, [FinancialPosition::CATEGORY_LOAN, FinancialPosition::CATEGORY_SAVINGS], true)
                && ($position->account_number === null || $position->account_number === '')) {
                $position->update([
                    'account_number' => ($category === FinancialPosition::CATEGORY_LOAN ? 'LN' : 'SV').'-'.$position->id,
                ]);
            }

            FinancialPositionMovement::query()->create([
                'financial_position_id' => $position->id,
                'company_id' => $position->company_id,
                'user_id' => $request->user()->id,
                'type' => FinancialPositionMovement::TYPE_OPENING,
                'amount_cents' => $principalCents,
                'balance_after_cents' => $principalCents,
                'memo' => __('Opening balance'),
            ]);
        });

        return redirect()->route('finance.positions.index', array_merge(
            ['category' => $category],
            $this->financeIndexQuery($request),
        ))->with('status', __('Record saved.'));
    }

    public function edit(Request $request, string $category, int $position): Response
    {
        $category = $this->validatedCategory($category);

        $company = $this->accountingCompany($request);

        $record = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', $category)
            ->with('member')
            ->findOrFail($position);

        $this->authorize('update', $record);

        return Inertia::render('Accounting/Finance/Edit', [
            'category' => $category,
            'categoryLabel' => $this->categoryLabel($category),
            'approved_members' => $this->approvedMembersForSelect($company->id, $category, $record),
            'position' => [
                'id' => $record->id,
                'member_id' => $record->member_id,
                'title' => $record->title,
                'principal' => round(((int) $record->principal_cents) / 100, 2),
                'annual_interest_rate_percent' => (float) $record->annual_interest_rate_percent,
                'start_date' => $record->start_date?->toDateString() ?? '',
                'notes' => $record->notes,
                'annual_interest_cents' => $record->annualInterestCents(),
                'monthly_interest_cents' => $record->monthlyInterestCents(),
                'account_number' => $record->account_number,
            ],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function update(Request $request, string $category, int $position): RedirectResponse
    {
        $category = $this->validatedCategory($category);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', $category)
            ->findOrFail($position);

        $this->authorize('update', $record);

        $validated = $this->validatedPayload($request, $record->category, $record->company_id, $record->id);

        $update = [
            'member_id' => $validated['member_id'] ?? null,
            'title' => $validated['title'],
            'principal_cents' => (int) round(((float) $validated['principal']) * 100),
            'annual_interest_rate_percent' => $validated['annual_interest_rate_percent'] ?? 0,
            'start_date' => $validated['start_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        if (in_array($record->category, [FinancialPosition::CATEGORY_LOAN, FinancialPosition::CATEGORY_SAVINGS], true)) {
            $raw = $validated['account_number'] ?? null;
            $acct = is_string($raw) ? trim($raw) : '';
            if ($acct === '') {
                $acct = ($record->category === FinancialPosition::CATEGORY_LOAN ? 'LN' : 'SV').'-'.$record->id;
            }
            $update['account_number'] = $acct;
        }

        $record->update($update);

        return redirect()->route('finance.positions.index', array_merge(
            ['category' => $category],
            $this->companyQuery($request),
        ))->with('status', __('Record updated.'));
    }

    public function destroy(Request $request, string $category, int $position): RedirectResponse
    {
        $category = $this->validatedCategory($category);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', $category)
            ->findOrFail($position);

        $this->authorize('delete', $record);

        try {
            $record->delete();
        } catch (QueryException $e) {
            // FK-protected references (group deposits/collections and similar) must be removed first.
            if ((string) $e->getCode() === '23000') {
                return back()->withErrors([
                    'delete' => __('This record cannot be deleted because it is already used in transactions. Remove related transactions first.'),
                ]);
            }

            throw $e;
        }

        return redirect()->route('finance.positions.index', array_merge(
            ['category' => $category],
            $this->companyQuery($request),
        ))->with('status', __('Record removed.'));
    }

    public function show(Request $request, string $category, int $position): Response
    {
        $category = $this->validatedCategory($category);

        $company = $this->accountingCompany($request);

        $record = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', $category)
            ->with([
                'member:id,name,status,reference_code,member_number',
                'loanProduct:id,product_code,name',
                'savingsProduct:id,product_code,name',
            ])
            ->findOrFail($position);

        $this->authorize('view', $record);

        $workspace = $request->query('workspace', 'full');
        if (! in_array($workspace, ['full', 'front', 'back'], true)) {
            $workspace = 'full';
        }

        $year = (int) $request->query('year', date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $accruals = FinancialPositionAccrual::query()
            ->where('financial_position_id', $record->id)
            ->where('accrual_year', $year)
            ->orderBy('accrual_month')
            ->orderBy('kind')
            ->get();

        $accrualRows = $accruals->map(fn (FinancialPositionAccrual $a) => [
            'id' => $a->id,
            'accrual_year' => $a->accrual_year,
            'accrual_month' => $a->accrual_month,
            'amount_cents' => $a->amount_cents,
            'kind' => $a->kind,
            'posted' => $a->isPosted(),
            'journal_entry_id' => $a->journal_entry_id,
        ])->values()->all();

        $savingsQuarters = [];
        $depositInterestTax = null;
        $liabilityAccountOptions = [];
        if ($category === FinancialPosition::CATEGORY_SAVINGS) {
            $financeAccrualService = app(FinanceAccrualService::class);
            for ($q = 1; $q <= 4; $q++) {
                $rows = $financeAccrualService->unpaidSavingsAccrualsForQuarter($record, $year, $q);
                $savingsQuarters[] = [
                    'quarter' => $q,
                    'unpaid_count' => $rows->count(),
                    'total_cents' => $financeAccrualService->totalCents($rows),
                    'ready' => $rows->count() === 3,
                ];
            }
            $cbs = $company->normalizedCbsConfiguration();
            $pct = (float) $cbs['deposit_interest_withholding_tax_percent'];
            $depositInterestTax = [
                'withholding_tax_percent' => $pct,
                'tax_payable_chart_account_id' => $cbs['deposit_interest_tax_payable_chart_account_id'],
            ];
            $liabilityAccountOptions = $this->liabilityChartAccountOptions($company->id);
        }

        return Inertia::render('Accounting/Finance/Show', [
            'category' => $category,
            'categoryLabel' => $this->categoryLabel($category),
            'workspace' => $workspace,
            'position' => [
                'id' => $record->id,
                'title' => $record->title,
                'account_number' => $record->account_number,
                'principal_cents' => (int) $record->principal_cents,
                'annual_interest_rate_percent' => (string) $record->annual_interest_rate_percent,
                'monthly_interest_cents' => $record->monthlyInterestCents(),
                'uses_banking_monthly' => $record->usesBankingMonthlyRate(),
                'member' => $record->member ? [
                    'id' => $record->member->id,
                    'name' => $record->member->name,
                    'status' => $record->member->status,
                    'reference_code' => $record->member->reference_code,
                    'member_number' => $record->member->member_number,
                ] : null,
                'member_finance_ok' => $record->memberApprovedForFinance(),
                'uses_structured_loan' => $record->usesStructuredLoanFlow(),
                'loan_workflow_status' => $record->loan_workflow_status,
                'is_loan_pending_approval' => $record->isLoanPendingApproval(),
                'is_loan_operational' => $record->isLoanOperational(),
                'proposed_account_number' => $record->proposedCatalogAccountNumber(),
                'sanctioned_amount_cents' => $record->sanctioned_amount_cents !== null ? (int) $record->sanctioned_amount_cents : null,
                'loan_product' => $record->loanProduct ? [
                    'product_code' => $record->loanProduct->product_code,
                    'name' => $record->loanProduct->name,
                ] : null,
                'uses_structured_savings' => $record->usesStructuredSavingsFlow(),
                'savings_workflow_status' => $record->savings_workflow_status,
                'is_savings_pending_approval' => $record->isSavingsPendingApproval(),
                'is_savings_operational' => $record->isSavingsOperational(),
                'proposed_savings_account_number' => $record->proposedCatalogAccountNumber(),
                'savings_product' => $record->savingsProduct ? [
                    'product_code' => $record->savingsProduct->product_code,
                    'name' => $record->savingsProduct->name,
                ] : null,
            ],
            'year' => $year,
            'accruals' => $accrualRows,
            'savings_quarters' => $savingsQuarters,
            'deposit_interest_tax' => $depositInterestTax,
            'liability_account_options' => $liabilityAccountOptions,
            'accounts' => $this->chartAccountOptions($company->id),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function syncAccrualYear(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $category = $this->validatedCategory($category);

        $company = $this->accountingCompany($request);

        $record = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', $category)
            ->findOrFail($position);

        $this->authorize('update', $record);

        if (! $record->usesBankingMonthlyRate()) {
            return back()->withErrors([
                'sync' => __('Only loan and savings positions support automated monthly schedules.'),
            ]);
        }

        if (! $record->memberApprovedForFinance()) {
            return back()->withErrors([
                'member' => __('Assign an approved member on Edit before syncing this schedule.'),
            ]);
        }

        if ($r = $this->redirectIfStructuredCatalogNotOperational($record, 'sync')) {
            return $r;
        }

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $touched = app(FinanceAccrualService::class)->syncMonthlyAccrualsForYear(
            $record,
            (int) $validated['year'],
        );

        return back()->with('status', __('Schedule updated (:n months).', ['n' => $touched]));
    }

    public function storeManualAccrual(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $category = $this->validatedCategory($category);

        $company = $this->accountingCompany($request);

        $record = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', $category)
            ->findOrFail($position);

        $this->authorize('update', $record);

        if ($category !== FinancialPosition::CATEGORY_INVESTMENT) {
            return back()->withErrors([
                'manual' => __('Manual accruals apply to investments only.'),
            ]);
        }

        $record->loadMissing('member');

        if (! $record->memberApprovedForFinance()) {
            return back()->withErrors([
                'manual' => __('Link an approved member on Edit before recording investment accruals.'),
            ]);
        }

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $existing = FinancialPositionAccrual::query()
            ->where('financial_position_id', $record->id)
            ->where('accrual_year', (int) $validated['year'])
            ->where('accrual_month', (int) $validated['month'])
            ->where('kind', FinancialPositionAccrual::KIND_INVESTMENT_MANUAL)
            ->first();

        if ($existing?->isPosted()) {
            return back()->withErrors([
                'amount' => __('This month is already posted to the ledger.'),
            ]);
        }

        $cents = (int) round(((float) $validated['amount']) * 100);

        FinancialPositionAccrual::query()->updateOrCreate(
            [
                'financial_position_id' => $record->id,
                'accrual_year' => (int) $validated['year'],
                'accrual_month' => (int) $validated['month'],
                'kind' => FinancialPositionAccrual::KIND_INVESTMENT_MANUAL,
            ],
            [
                'company_id' => $record->company_id,
                'amount_cents' => $cents,
            ],
        );

        return back()->with('status', __('Accrual saved.'));
    }

    public function postAccrualToLedger(
        Request $request,
        string $category,
        int $position,
        int $accrual,
    ): RedirectResponse {
        $this->validateAdminCompanySelection($request);

        $category = $this->validatedCategory($category);

        $company = $this->accountingCompany($request);

        $record = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', $category)
            ->findOrFail($position);

        $this->authorize('update', $record);

        if (! $record->memberApprovedForFinance()) {
            return back()->withErrors([
                'member' => __('Only approved members can post finance transactions to the ledger.'),
            ]);
        }

        if ($r = $this->redirectIfStructuredCatalogNotOperational($record, 'member')) {
            return $r;
        }

        $record->loadMissing('member');

        $accrualModel = FinancialPositionAccrual::query()
            ->whereKey($accrual)
            ->where('financial_position_id', $record->id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        if ($accrualModel->kind === FinancialPositionAccrual::KIND_SAVINGS_MONTHLY) {
            return back()->withErrors([
                'kind' => __('Savings interest is posted once per quarter.'),
            ]);
        }

        if ($accrualModel->isPosted()) {
            return back()->withErrors([
                'posted' => __('Already posted.'),
            ]);
        }

        if ($accrualModel->amount_cents <= 0) {
            return back()->withErrors([
                'amount' => __('Nothing to post.'),
            ]);
        }

        $validated = $this->validatedLedgerPosting($request, $company->id);

        $monthPadded = str_pad((string) $accrualModel->accrual_month, 2, '0', STR_PAD_LEFT);
        $memberSuffix = $this->financeMemberMemoSuffix($record);
        $memo = match ($accrualModel->kind) {
            FinancialPositionAccrual::KIND_LOAN_MONTHLY => __('Loan interest :title :period', [
                'title' => $record->title,
                'period' => $accrualModel->accrual_year.'-'.$monthPadded,
            ]).$memberSuffix,
            default => __('Investment return :title :period', [
                'title' => $record->title,
                'period' => $accrualModel->accrual_year.'-'.$monthPadded,
            ]).$memberSuffix,
        };

        $journalService = app(FinanceJournalPostingService::class);

        try {
            $entry = $journalService->postTwoLineJournal(
                $company->id,
                $request->user(),
                $validated['transaction_date'],
                $memo,
                $validated['reference'] ?? null,
                $accrualModel->amount_cents,
                (int) $validated['debit_chart_account_id'],
                (int) $validated['credit_chart_account_id'],
                $record->member_id,
                $record->category,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['ledger' => $e->getMessage()]);
        }

        $accrualModel->update(['journal_entry_id' => $entry->id]);

        return back()->with('status', __('Posted to journal.'));
    }

    public function postSavingsQuarterToLedger(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $category = $this->validatedCategory($category);

        $company = $this->accountingCompany($request);

        $record = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', $category)
            ->findOrFail($position);

        $this->authorize('update', $record);

        if ($record->category !== FinancialPosition::CATEGORY_SAVINGS) {
            return back()->withErrors([
                'quarter' => __('Quarter posting applies to savings only.'),
            ]);
        }

        if (! $record->memberApprovedForFinance()) {
            return back()->withErrors([
                'member' => __('Only approved members can post savings to the ledger.'),
            ]);
        }

        if ($r = $this->redirectIfStructuredCatalogNotOperational($record, 'quarter')) {
            return $r;
        }

        $record->loadMissing('member');

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'quarter' => ['required', 'integer', 'min:1', 'max:4'],
            'transaction_date' => ['required', 'date'],
            'debit_chart_account_id' => [
                'required',
                'integer',
                Rule::exists('chart_accounts', 'id')->where(
                    fn ($q) => $q->where('company_id', $company->id)
                        ->where('approval_status', ChartAccount::STATUS_APPROVED)
                ),
            ],
            'credit_chart_account_id' => [
                'required',
                'integer',
                'different:debit_chart_account_id',
                Rule::exists('chart_accounts', 'id')->where(
                    fn ($q) => $q->where('company_id', $company->id)
                        ->where('approval_status', ChartAccount::STATUS_APPROVED)
                ),
            ],
            'tax_payable_chart_account_id' => [
                'nullable',
                'integer',
                Rule::exists('chart_accounts', 'id')->where(
                    fn ($q) => $q->where('company_id', $company->id)
                        ->where('approval_status', ChartAccount::STATUS_APPROVED)
                        ->where('type', ChartAccount::TYPE_LIABILITY)
                ),
            ],
            'reference' => ['nullable', 'string', 'max:64'],
        ]);

        $service = app(FinanceAccrualService::class);
        $rows = $service->unpaidSavingsAccrualsForQuarter(
            $record,
            (int) $validated['year'],
            (int) $validated['quarter'],
        );

        if ($rows->count() !== 3) {
            return back()->withErrors([
                'quarter' => __('All three months in the quarter must have unpaid accruals.'),
            ]);
        }

        $total = $service->totalCents($rows);

        if ($total <= 0) {
            return back()->withErrors([
                'amount' => __('Nothing to post for this quarter.'),
            ]);
        }

        $journalService = app(FinanceJournalPostingService::class);

        $memo = __('Savings interest :title (quarter :quarter, :year)', [
            'title' => $record->title,
            'quarter' => (int) $validated['quarter'],
            'year' => (int) $validated['year'],
        ]).$this->financeMemberMemoSuffix($record);

        $cbs = $company->normalizedCbsConfiguration();
        $taxPercent = (float) $cbs['deposit_interest_withholding_tax_percent'];
        $taxCents = $taxPercent > 0
            ? (int) round($total * $taxPercent / 100.0)
            : 0;
        $useWithholding = $taxPercent > 0 && $taxCents > 0;

        $taxAccountId = 0;
        if (! empty($validated['tax_payable_chart_account_id'])) {
            $taxAccountId = (int) $validated['tax_payable_chart_account_id'];
        } else {
            $defaultTax = $cbs['deposit_interest_tax_payable_chart_account_id'] ?? null;
            if ($defaultTax !== null && $defaultTax !== '') {
                $taxAccountId = (int) $defaultTax;
            }
        }

        if ($useWithholding && $taxAccountId <= 0) {
            return back()->withErrors([
                'tax_payable_chart_account_id' => __('Select a tax payable liability account, or set a default under Company → Configuration.'),
            ]);
        }

        try {
            DB::transaction(function () use (
                $journalService,
                $company,
                $request,
                $validated,
                $total,
                $memo,
                $rows,
                $record,
                $useWithholding,
                $taxCents,
                $taxAccountId,
            ) {
                if ($useWithholding) {
                    $grossCents = $total;
                    $netCents = $grossCents - $taxCents;
                    $lines = [
                        [
                            'chart_account_id' => (int) $validated['debit_chart_account_id'],
                            'debit_cents' => $grossCents,
                            'credit_cents' => 0,
                            'description' => __('Gross interest on deposits'),
                        ],
                        [
                            'chart_account_id' => (int) $validated['credit_chart_account_id'],
                            'debit_cents' => 0,
                            'credit_cents' => $netCents,
                            'description' => __('Interest credited to member deposit (net of withholding)'),
                        ],
                        [
                            'chart_account_id' => $taxAccountId,
                            'debit_cents' => 0,
                            'credit_cents' => $taxCents,
                            'description' => __('Tax withheld on deposit interest (payable)'),
                        ],
                    ];
                    $entry = $journalService->postBalancedLinesJournal(
                        $company->id,
                        $request->user(),
                        $validated['transaction_date'],
                        $memo,
                        $validated['reference'] ?? null,
                        $lines,
                        $record->member_id,
                        $record->category,
                    );
                } else {
                    $entry = $journalService->postTwoLineJournal(
                        $company->id,
                        $request->user(),
                        $validated['transaction_date'],
                        $memo,
                        $validated['reference'] ?? null,
                        $total,
                        (int) $validated['debit_chart_account_id'],
                        (int) $validated['credit_chart_account_id'],
                        $record->member_id,
                        $record->category,
                    );
                }

                FinancialPositionAccrual::query()
                    ->whereIn('id', $rows->pluck('id'))
                    ->update(['journal_entry_id' => $entry->id]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['ledger' => $e->getMessage()]);
        }

        return back()->with('status', __('Quarter interest posted to journal.'));
    }

    public function movementsData(Request $request, string $category, int $position): JsonResponse
    {
        $category = $this->validatedCategory($category);

        $company = $this->accountingCompany($request);

        $record = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', $category)
            ->findOrFail($position);

        $this->authorize('view', $record);
        $this->assertLoanOrSavingsProduct($record);

        if ($record->usesStructuredCatalogWorkflow() && ! $record->isStructuredCatalogOperational()) {
            return $this->structuredCatalogNotActiveJsonResponse();
        }

        $rows = $record->movements()
            ->with('user:id,name')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (FinancialPositionMovement $m) => [
                'id' => $m->id,
                'type' => $m->type,
                'type_label' => $this->movementTypeLabel($m->type),
                'amount_cents' => (int) $m->amount_cents,
                'balance_after_cents' => (int) $m->balance_after_cents,
                'memo' => $m->memo,
                'created_at' => $m->created_at?->toIso8601String(),
                'user_name' => $m->user?->name,
                'journal_entry_id' => $m->journal_entry_id,
            ]);

        return response()->json([
            'position' => [
                'id' => $record->id,
                'title' => $record->title,
                'category' => $record->category,
                'account_number' => $record->account_number,
                'principal_cents' => (int) $record->principal_cents,
                'uses_structured_loan' => $record->usesStructuredLoanFlow(),
                'is_loan_operational' => $record->isLoanOperational(),
                'uses_structured_savings' => $record->usesStructuredSavingsFlow(),
                'is_savings_operational' => $record->isSavingsOperational(),
            ],
            'movements' => $rows,
        ]);
    }

    public function statement(Request $request, string $category, int $position): Response
    {
        $category = $this->validatedCategory($category);

        $company = $this->accountingCompany($request);

        $record = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', $category)
            ->with(['member:id,name,member_number', 'movements' => fn ($q) => $q->orderBy('created_at')->orderBy('id')])
            ->findOrFail($position);

        $this->authorize('view', $record);
        $this->assertLoanOrSavingsProduct($record);

        if ($record->usesStructuredCatalogWorkflow() && ! $record->isStructuredCatalogOperational()) {
            abort(403, __('The statement is available after this account is approved and activated.'));
        }

        $movements = $record->movements->map(fn (FinancialPositionMovement $m) => [
            'type' => $m->type,
            'type_label' => $this->movementTypeLabel($m->type),
            'amount_cents' => (int) $m->amount_cents,
            'balance_after_cents' => (int) $m->balance_after_cents,
            'memo' => $m->memo,
            'at' => $m->created_at?->toDateTimeString(),
        ])->values()->all();

        return Inertia::render('Accounting/Finance/Statement', [
            'category' => $category,
            'categoryLabel' => $this->categoryLabel($category),
            'letterhead' => $this->companyLetterhead($company),
            'position' => [
                'id' => $record->id,
                'title' => $record->title,
                'account_number' => $record->account_number,
                'principal_cents' => (int) $record->principal_cents,
                'member' => $record->member ? [
                    'name' => $record->member->name,
                    'member_number' => $record->member->member_number,
                ] : null,
            ],
            'movements' => $movements,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function storeDeposit(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $record = $this->positionForCompany($request, $category, $position);
        $this->authorize('update', $record);
        $this->assertLoanOrSavingsProduct($record);

        if ($r = $this->denyLegacyMovementForStructuredCatalog($record, 'deposit')) {
            return $r;
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'memo' => ['nullable', 'string', 'max:2000'],
        ]);

        $add = (int) round(((float) $validated['amount']) * 100);

        if ($record->category === FinancialPosition::CATEGORY_SAVINGS) {
            $record->loadMissing('member');
            if (! $record->memberApprovedForFinance()) {
                return back()->withErrors([
                    'member' => __('Link an approved member before depositing.'),
                ]);
            }

            $memberPersonalAccountId = $this->ensureMemberPersonalChartAccount(
                $record,
                forLoan: false,
                actor: $request->user(),
            );

            $ledger = $this->validatedLedgerPosting(
                $request,
                $record->company_id,
                requireDebit: true,
                requireCredit: false,
            );
            $ledger['credit_chart_account_id'] = $memberPersonalAccountId;

            if ((int) $ledger['debit_chart_account_id'] === $memberPersonalAccountId) {
                return back()->withErrors([
                    'debit_chart_account_id' => __('Cash / bank account must be different from the member personal account.'),
                ]);
            }

            $memo = __('Savings deposit :title', ['title' => $record->title]).$this->financeMemberMemoSuffix($record);
            if (! empty($validated['memo'])) {
                $memo .= ' — '.$validated['memo'];
            }

            $journalService = app(FinanceJournalPostingService::class);
            $postedJournalId = null;

            try {
                DB::transaction(function () use (
                    $journalService,
                    $request,
                    $ledger,
                    $add,
                    $memo,
                    $record,
                    $validated,
                    &$postedJournalId,
                ) {
                    $entry = $journalService->postTwoLineJournal(
                        $record->company_id,
                        $request->user(),
                        $ledger['transaction_date'],
                        $memo,
                        $ledger['reference'] ?? null,
                        $add,
                        (int) $ledger['debit_chart_account_id'],
                        (int) $ledger['credit_chart_account_id'],
                        $record->member_id,
                        FinancialPosition::CATEGORY_SAVINGS,
                    );
                    $postedJournalId = (int) $entry->id;

                    $record->increment('principal_cents', $add);
                    $record->refresh();

                    FinancialPositionMovement::query()->create([
                        'financial_position_id' => $record->id,
                        'company_id' => $record->company_id,
                        'user_id' => $request->user()->id,
                        'type' => FinancialPositionMovement::TYPE_DEPOSIT,
                        'amount_cents' => $add,
                        'balance_after_cents' => (int) $record->principal_cents,
                        'memo' => $validated['memo'] ?? null,
                        'journal_entry_id' => $entry->id,
                    ]);
                });
            } catch (InvalidArgumentException $e) {
                return back()->withErrors(['ledger' => $e->getMessage()]);
            }

            return back()
                ->with('status', __('Deposit recorded and posted to the journal.'))
                ->with('posted_journal_id', $postedJournalId);
        }

        if ($record->category === FinancialPosition::CATEGORY_LOAN) {
            $record->loadMissing('member');
            if (! $record->memberApprovedForFinance()) {
                return back()->withErrors([
                    'member' => __('Link an approved member before disbursement.'),
                ]);
            }

            $memberPersonalAccountId = $this->ensureMemberPersonalChartAccount(
                $record,
                forLoan: true,
                actor: $request->user(),
            );

            $ledger = $this->validatedLedgerPosting(
                $request,
                $record->company_id,
                requireDebit: false,
                requireCredit: true,
            );
            $ledger['debit_chart_account_id'] = $memberPersonalAccountId;

            if ((int) $ledger['credit_chart_account_id'] === $memberPersonalAccountId) {
                return back()->withErrors([
                    'credit_chart_account_id' => __('Cash / bank account must be different from the member loan account.'),
                ]);
            }

            $memo = __('Loan disbursement :title', ['title' => $record->title]).$this->financeMemberMemoSuffix($record);
            if (! empty($validated['memo'])) {
                $memo .= ' — '.$validated['memo'];
            }

            $journalService = app(FinanceJournalPostingService::class);
            $postedJournalId = null;

            try {
                DB::transaction(function () use (
                    $journalService,
                    $request,
                    $ledger,
                    $add,
                    $memo,
                    $record,
                    $validated,
                    &$postedJournalId,
                ) {
                    $entry = $journalService->postTwoLineJournal(
                        $record->company_id,
                        $request->user(),
                        $ledger['transaction_date'],
                        $memo,
                        $ledger['reference'] ?? null,
                        $add,
                        (int) $ledger['debit_chart_account_id'],
                        (int) $ledger['credit_chart_account_id'],
                        $record->member_id,
                        FinancialPosition::CATEGORY_LOAN,
                    );
                    $postedJournalId = (int) $entry->id;

                    $record->increment('principal_cents', $add);
                    $record->refresh();

                    FinancialPositionMovement::query()->create([
                        'financial_position_id' => $record->id,
                        'company_id' => $record->company_id,
                        'user_id' => $request->user()->id,
                        'type' => FinancialPositionMovement::TYPE_DEPOSIT,
                        'amount_cents' => $add,
                        'balance_after_cents' => (int) $record->principal_cents,
                        'memo' => $validated['memo'] ?? null,
                        'journal_entry_id' => $entry->id,
                    ]);
                });
            } catch (InvalidArgumentException $e) {
                return back()->withErrors(['ledger' => $e->getMessage()]);
            }

            return back()
                ->with('status', __('Disbursement recorded and posted to the journal.'))
                ->with('posted_journal_id', $postedJournalId);
        }

        DB::transaction(function () use ($record, $request, $add, $validated) {
            $record->increment('principal_cents', $add);
            $record->refresh();
            FinancialPositionMovement::query()->create([
                'financial_position_id' => $record->id,
                'company_id' => $record->company_id,
                'user_id' => $request->user()->id,
                'type' => FinancialPositionMovement::TYPE_DEPOSIT,
                'amount_cents' => $add,
                'balance_after_cents' => (int) $record->principal_cents,
                'memo' => $validated['memo'] ?? null,
            ]);
        });

        return back()->with('status', __('Deposit recorded.'));
    }

    public function storeWithdrawal(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $record = $this->positionForCompany($request, $category, $position);
        $this->authorize('update', $record);
        $this->assertLoanOrSavingsProduct($record);

        if ($r = $this->denyLegacyMovementForStructuredCatalog($record, 'withdraw')) {
            return $r;
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'memo' => ['nullable', 'string', 'max:2000'],
        ]);

        $sub = (int) round(((float) $validated['amount']) * 100);

        if ($record->principal_cents < $sub) {
            return back()->withErrors([
                'amount' => __('Withdrawal exceeds current principal / balance.'),
            ]);
        }

        if ($record->category === FinancialPosition::CATEGORY_SAVINGS) {
            $record->loadMissing('member');
            if (! $record->memberApprovedForFinance()) {
                return back()->withErrors([
                    'member' => __('Link an approved member before withdrawing.'),
                ]);
            }

            $memberPersonalAccountId = $this->ensureMemberPersonalChartAccount(
                $record,
                forLoan: false,
                actor: $request->user(),
            );

            $ledger = $this->validatedLedgerPosting(
                $request,
                $record->company_id,
                requireDebit: false,
                requireCredit: true,
            );
            $ledger['debit_chart_account_id'] = $memberPersonalAccountId;

            if ((int) $ledger['credit_chart_account_id'] === $memberPersonalAccountId) {
                return back()->withErrors([
                    'credit_chart_account_id' => __('Cash / bank account must be different from the member personal account.'),
                ]);
            }

            $memo = __('Savings withdrawal :title', ['title' => $record->title]).$this->financeMemberMemoSuffix($record);
            if (! empty($validated['memo'])) {
                $memo .= ' — '.$validated['memo'];
            }

            $journalService = app(FinanceJournalPostingService::class);
            $postedJournalId = null;

            try {
                DB::transaction(function () use (
                    $journalService,
                    $request,
                    $ledger,
                    $sub,
                    $memo,
                    $record,
                    $validated,
                    &$postedJournalId,
                ) {
                    $entry = $journalService->postTwoLineJournal(
                        $record->company_id,
                        $request->user(),
                        $ledger['transaction_date'],
                        $memo,
                        $ledger['reference'] ?? null,
                        $sub,
                        (int) $ledger['debit_chart_account_id'],
                        (int) $ledger['credit_chart_account_id'],
                        $record->member_id,
                        FinancialPosition::CATEGORY_SAVINGS,
                    );
                    $postedJournalId = (int) $entry->id;

                    $record->decrement('principal_cents', $sub);
                    $record->refresh();

                    FinancialPositionMovement::query()->create([
                        'financial_position_id' => $record->id,
                        'company_id' => $record->company_id,
                        'user_id' => $request->user()->id,
                        'type' => FinancialPositionMovement::TYPE_WITHDRAWAL,
                        'amount_cents' => -$sub,
                        'balance_after_cents' => (int) $record->principal_cents,
                        'memo' => $validated['memo'] ?? null,
                        'journal_entry_id' => $entry->id,
                    ]);
                });
            } catch (InvalidArgumentException $e) {
                return back()->withErrors(['ledger' => $e->getMessage()]);
            }

            return back()
                ->with('status', __('Withdrawal recorded and posted to the journal.'))
                ->with('posted_journal_id', $postedJournalId);
        }

        if ($record->category === FinancialPosition::CATEGORY_LOAN) {
            $record->loadMissing('member');
            if (! $record->memberApprovedForFinance()) {
                return back()->withErrors([
                    'member' => __('Link an approved member before repayment.'),
                ]);
            }

            $memberPersonalAccountId = $this->ensureMemberPersonalChartAccount(
                $record,
                forLoan: true,
                actor: $request->user(),
            );

            $ledger = $this->validatedLedgerPosting(
                $request,
                $record->company_id,
                requireDebit: true,
                requireCredit: false,
            );
            $ledger['credit_chart_account_id'] = $memberPersonalAccountId;

            if ((int) $ledger['debit_chart_account_id'] === $memberPersonalAccountId) {
                return back()->withErrors([
                    'debit_chart_account_id' => __('Cash / bank account must be different from the member loan account.'),
                ]);
            }

            $memo = __('Loan principal repayment :title', ['title' => $record->title]).$this->financeMemberMemoSuffix($record);
            if (! empty($validated['memo'])) {
                $memo .= ' — '.$validated['memo'];
            }

            $journalService = app(FinanceJournalPostingService::class);
            $postedJournalId = null;

            try {
                DB::transaction(function () use (
                    $journalService,
                    $request,
                    $ledger,
                    $sub,
                    $memo,
                    $record,
                    $validated,
                    &$postedJournalId,
                ) {
                    $entry = $journalService->postTwoLineJournal(
                        $record->company_id,
                        $request->user(),
                        $ledger['transaction_date'],
                        $memo,
                        $ledger['reference'] ?? null,
                        $sub,
                        (int) $ledger['debit_chart_account_id'],
                        (int) $ledger['credit_chart_account_id'],
                        $record->member_id,
                        FinancialPosition::CATEGORY_LOAN,
                    );
                    $postedJournalId = (int) $entry->id;

                    $record->decrement('principal_cents', $sub);
                    $record->refresh();

                    FinancialPositionMovement::query()->create([
                        'financial_position_id' => $record->id,
                        'company_id' => $record->company_id,
                        'user_id' => $request->user()->id,
                        'type' => FinancialPositionMovement::TYPE_WITHDRAWAL,
                        'amount_cents' => -$sub,
                        'balance_after_cents' => (int) $record->principal_cents,
                        'memo' => $validated['memo'] ?? null,
                        'journal_entry_id' => $entry->id,
                    ]);
                });
            } catch (InvalidArgumentException $e) {
                return back()->withErrors(['ledger' => $e->getMessage()]);
            }

            return back()
                ->with('status', __('Repayment recorded and posted to the journal.'))
                ->with('posted_journal_id', $postedJournalId);
        }

        DB::transaction(function () use ($record, $request, $sub, $validated) {
            $record->decrement('principal_cents', $sub);
            $record->refresh();
            FinancialPositionMovement::query()->create([
                'financial_position_id' => $record->id,
                'company_id' => $record->company_id,
                'user_id' => $request->user()->id,
                'type' => FinancialPositionMovement::TYPE_WITHDRAWAL,
                'amount_cents' => -$sub,
                'balance_after_cents' => (int) $record->principal_cents,
                'memo' => $validated['memo'] ?? null,
            ]);
        });

        return back()->with('status', __('Withdrawal recorded.'));
    }

    public function storeAdjustment(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $record = $this->positionForCompany($request, $category, $position);
        $this->authorize('update', $record);
        $this->assertLoanOrSavingsProduct($record);

        if ($r = $this->redirectIfStructuredCatalogNotOperational($record, 'flow')) {
            return $r;
        }

        if ($r = $this->denyLegacyMovementForStructuredCatalog($record, 'adjustment')) {
            return $r;
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric'],
            'memo' => ['nullable', 'string', 'max:2000'],
        ]);

        $delta = (int) round(((float) $validated['amount']) * 100);
        $new = (int) $record->principal_cents + $delta;

        if ($new < 0) {
            return back()->withErrors([
                'amount' => __('Resulting balance cannot be negative.'),
            ]);
        }

        DB::transaction(function () use ($record, $request, $delta, $new, $validated) {
            $record->update(['principal_cents' => $new]);
            FinancialPositionMovement::query()->create([
                'financial_position_id' => $record->id,
                'company_id' => $record->company_id,
                'user_id' => $request->user()->id,
                'type' => FinancialPositionMovement::TYPE_ADJUSTMENT,
                'amount_cents' => $delta,
                'balance_after_cents' => $new,
                'memo' => $validated['memo'] ?? null,
            ]);
        });

        return back()->with('status', __('Adjustment recorded.'));
    }

    public function approveLoanApplication(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);
        $category = $this->validatedCategory($category);
        abort_unless($category === FinancialPosition::CATEGORY_LOAN, 404);
        $record = $this->positionForCompany($request, $category, $position);
        $this->authorize('approve', $record);

        return $this->finalizeCatalogApproval($record, forLoan: true);
    }

    public function rejectLoanApplication(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);
        $category = $this->validatedCategory($category);
        abort_unless($category === FinancialPosition::CATEGORY_LOAN, 404);
        $record = $this->positionForCompany($request, $category, $position);
        $this->authorize('reject', $record);

        return $this->finalizeCatalogRejection($record, forLoan: true);
    }

    public function approveSavingsApplication(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);
        $category = $this->validatedCategory($category);
        abort_unless($category === FinancialPosition::CATEGORY_SAVINGS, 404);
        $record = $this->positionForCompany($request, $category, $position);
        $this->authorize('approve', $record);

        return $this->finalizeCatalogApproval($record, forLoan: false);
    }

    public function rejectSavingsApplication(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);
        $category = $this->validatedCategory($category);
        abort_unless($category === FinancialPosition::CATEGORY_SAVINGS, 404);
        $record = $this->positionForCompany($request, $category, $position);
        $this->authorize('reject', $record);

        return $this->finalizeCatalogRejection($record, forLoan: false);
    }

    public function storeDisbursement(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $category = $this->validatedCategory($category);
        abort_unless($category === FinancialPosition::CATEGORY_LOAN, 404);

        $record = $this->positionForCompany($request, $category, $position);
        $this->authorize('update', $record);
        $this->assertLoanOrSavingsProduct($record);

        if (! $record->usesStructuredLoanFlow()) {
            return back()->withErrors([
                'flow' => __('Disbursement with journal applies to product-based loans. Use the standard loan form for ad-hoc loans.'),
            ]);
        }

        if (! $record->isStructuredCatalogOperational()) {
            return back()->withErrors([
                'flow' => __('Approve this loan account before disbursing funds.'),
            ]);
        }

        if (! $record->memberApprovedForFinance()) {
            return back()->withErrors([
                'member' => __('Link an approved member before disbursing.'),
            ]);
        }

        $record->loadMissing('member');

        $validatedAmount = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'memo' => ['nullable', 'string', 'max:2000'],
        ]);

        $add = (int) round(((float) $validatedAmount['amount']) * 100);

        if ($record->sanctioned_amount_cents !== null
            && (int) $record->principal_cents + $add > (int) $record->sanctioned_amount_cents) {
            return back()->withErrors([
                'amount' => __('Disbursement would exceed the sanctioned principal for this application.'),
            ]);
        }

        $memberPersonalAccountId = $this->ensureMemberPersonalChartAccount(
            $record,
            forLoan: true,
            actor: $request->user(),
        );

        $ledger = $this->validatedLedgerPosting(
            $request,
            $record->company_id,
            requireDebit: false,
            requireCredit: true,
        );
        $ledger['debit_chart_account_id'] = $memberPersonalAccountId;

        if ((int) $ledger['credit_chart_account_id'] === $memberPersonalAccountId) {
            return back()->withErrors([
                'credit_chart_account_id' => __('Cash / bank account must be different from the member personal account.'),
            ]);
        }
        $memo = __('Loan disbursement :title', ['title' => $record->title]).$this->financeMemberMemoSuffix($record);
        if (! empty($validatedAmount['memo'])) {
            $memo .= ' — '.$validatedAmount['memo'];
        }

        $journalService = app(FinanceJournalPostingService::class);
        $postedJournalId = null;

        try {
            DB::transaction(function () use (
                $journalService,
                $request,
                $ledger,
                $add,
                $memo,
                $record,
                $validatedAmount,
                &$postedJournalId,
            ) {
                $entry = $journalService->postTwoLineJournal(
                    $record->company_id,
                    $request->user(),
                    $ledger['transaction_date'],
                    $memo,
                    $ledger['reference'] ?? null,
                    $add,
                    (int) $ledger['debit_chart_account_id'],
                    (int) $ledger['credit_chart_account_id'],
                    $record->member_id,
                    FinancialPosition::CATEGORY_LOAN,
                );
                $postedJournalId = (int) $entry->id;

                $record->increment('principal_cents', $add);
                $record->refresh();

                FinancialPositionMovement::query()->create([
                    'financial_position_id' => $record->id,
                    'company_id' => $record->company_id,
                    'user_id' => $request->user()->id,
                    'type' => FinancialPositionMovement::TYPE_DISBURSEMENT,
                    'amount_cents' => $add,
                    'balance_after_cents' => (int) $record->principal_cents,
                    'memo' => $validatedAmount['memo'] ?? null,
                    'journal_entry_id' => $entry->id,
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['ledger' => $e->getMessage()]);
        }

        return back()
            ->with('status', __('Disbursement recorded and posted to the journal.'))
            ->with('posted_journal_id', $postedJournalId);
    }

    public function storeInstallment(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $category = $this->validatedCategory($category);
        abort_unless($category === FinancialPosition::CATEGORY_LOAN, 404);

        $record = $this->positionForCompany($request, $category, $position);
        $this->authorize('update', $record);
        $this->assertLoanOrSavingsProduct($record);

        if (! $record->usesStructuredLoanFlow()) {
            return back()->withErrors([
                'flow' => __('Installment with journal applies to product-based loans.'),
            ]);
        }

        if (! $record->isStructuredCatalogOperational()) {
            return back()->withErrors([
                'flow' => __('Approve this loan account before recording installments.'),
            ]);
        }

        if (! $record->memberApprovedForFinance()) {
            return back()->withErrors([
                'member' => __('Link an approved member before recording installments.'),
            ]);
        }

        $record->loadMissing('member');

        $validatedAmount = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'memo' => ['nullable', 'string', 'max:2000'],
        ]);

        $sub = (int) round(((float) $validatedAmount['amount']) * 100);

        if ($record->principal_cents < $sub) {
            return back()->withErrors([
                'amount' => __('Installment exceeds outstanding principal.'),
            ]);
        }

        $memberPersonalAccountId = $this->ensureMemberPersonalChartAccount(
            $record,
            forLoan: true,
            actor: $request->user(),
        );

        $ledger = $this->validatedLedgerPosting(
            $request,
            $record->company_id,
            requireDebit: true,
            requireCredit: false,
        );
        $ledger['credit_chart_account_id'] = $memberPersonalAccountId;

        if ((int) $ledger['debit_chart_account_id'] === $memberPersonalAccountId) {
            return back()->withErrors([
                'debit_chart_account_id' => __('Cash / bank account must be different from the member personal account.'),
            ]);
        }
        $memo = __('Loan installment / principal repayment :title', ['title' => $record->title]).$this->financeMemberMemoSuffix($record);
        if (! empty($validatedAmount['memo'])) {
            $memo .= ' — '.$validatedAmount['memo'];
        }

        $journalService = app(FinanceJournalPostingService::class);
        $postedJournalId = null;

        try {
            DB::transaction(function () use (
                $journalService,
                $request,
                $ledger,
                $sub,
                $memo,
                $record,
                $validatedAmount,
                &$postedJournalId,
            ) {
                $entry = $journalService->postTwoLineJournal(
                    $record->company_id,
                    $request->user(),
                    $ledger['transaction_date'],
                    $memo,
                    $ledger['reference'] ?? null,
                    $sub,
                    (int) $ledger['debit_chart_account_id'],
                    (int) $ledger['credit_chart_account_id'],
                    $record->member_id,
                    FinancialPosition::CATEGORY_LOAN,
                );
                $postedJournalId = (int) $entry->id;

                $record->decrement('principal_cents', $sub);
                $record->refresh();

                FinancialPositionMovement::query()->create([
                    'financial_position_id' => $record->id,
                    'company_id' => $record->company_id,
                    'user_id' => $request->user()->id,
                    'type' => FinancialPositionMovement::TYPE_INSTALLMENT,
                    'amount_cents' => -$sub,
                    'balance_after_cents' => (int) $record->principal_cents,
                    'memo' => $validatedAmount['memo'] ?? null,
                    'journal_entry_id' => $entry->id,
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['ledger' => $e->getMessage()]);
        }

        return back()
            ->with('status', __('Installment recorded and posted to the journal.'))
            ->with('posted_journal_id', $postedJournalId);
    }

    public function storePenalty(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $category = $this->validatedCategory($category);
        abort_unless($category === FinancialPosition::CATEGORY_LOAN, 404);

        $record = $this->positionForCompany($request, $category, $position);
        $this->authorize('update', $record);
        $this->assertLoanOrSavingsProduct($record);

        if (! $record->usesStructuredLoanFlow()) {
            return back()->withErrors([
                'flow' => __('Penalty with journal applies to product-based loans.'),
            ]);
        }

        if (! $record->isStructuredCatalogOperational()) {
            return back()->withErrors([
                'flow' => __('Approve this loan account before recording penalties.'),
            ]);
        }

        if (! $record->memberApprovedForFinance()) {
            return back()->withErrors([
                'member' => __('Link an approved member before recording penalties.'),
            ]);
        }

        $record->loadMissing('member');

        $validatedAmount = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'memo' => ['nullable', 'string', 'max:2000'],
        ]);

        $add = (int) round(((float) $validatedAmount['amount']) * 100);

        $memberPersonalAccountId = $this->ensureMemberPersonalChartAccount(
            $record,
            forLoan: true,
            actor: $request->user(),
        );

        $ledger = $this->validatedLedgerPosting(
            $request,
            $record->company_id,
            requireDebit: false,
            requireCredit: true,
        );
        $ledger['debit_chart_account_id'] = $memberPersonalAccountId;

        if ((int) $ledger['credit_chart_account_id'] === $memberPersonalAccountId) {
            return back()->withErrors([
                'credit_chart_account_id' => __('Cash / bank account must be different from the member personal account.'),
            ]);
        }
        $memo = __('Loan penalty / late charge :title', ['title' => $record->title]).$this->financeMemberMemoSuffix($record);
        if (! empty($validatedAmount['memo'])) {
            $memo .= ' — '.$validatedAmount['memo'];
        }

        $journalService = app(FinanceJournalPostingService::class);
        $postedJournalId = null;

        try {
            DB::transaction(function () use (
                $journalService,
                $request,
                $ledger,
                $add,
                $memo,
                $record,
                $validatedAmount,
                &$postedJournalId,
            ) {
                $entry = $journalService->postTwoLineJournal(
                    $record->company_id,
                    $request->user(),
                    $ledger['transaction_date'],
                    $memo,
                    $ledger['reference'] ?? null,
                    $add,
                    (int) $ledger['debit_chart_account_id'],
                    (int) $ledger['credit_chart_account_id'],
                    $record->member_id,
                    FinancialPosition::CATEGORY_LOAN,
                );
                $postedJournalId = (int) $entry->id;

                $record->increment('principal_cents', $add);
                $record->refresh();

                FinancialPositionMovement::query()->create([
                    'financial_position_id' => $record->id,
                    'company_id' => $record->company_id,
                    'user_id' => $request->user()->id,
                    'type' => FinancialPositionMovement::TYPE_PENALTY,
                    'amount_cents' => $add,
                    'balance_after_cents' => (int) $record->principal_cents,
                    'memo' => $validatedAmount['memo'] ?? null,
                    'journal_entry_id' => $entry->id,
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['ledger' => $e->getMessage()]);
        }

        return back()
            ->with('status', __('Penalty recorded and posted to the journal.'))
            ->with('posted_journal_id', $postedJournalId);
    }

    public function storeStructuredSavingsDeposit(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $category = $this->validatedCategory($category);
        abort_unless($category === FinancialPosition::CATEGORY_SAVINGS, 404);

        $record = $this->positionForCompany($request, $category, $position);
        $this->authorize('update', $record);
        $this->assertLoanOrSavingsProduct($record);

        if (! $record->usesStructuredSavingsFlow()) {
            return back()->withErrors([
                'flow' => __('This action applies to product-based savings only.'),
            ]);
        }

        if (! $record->isStructuredCatalogOperational()) {
            return back()->withErrors([
                'flow' => __('Approve this savings account before recording deposits.'),
            ]);
        }

        if (! $record->memberApprovedForFinance()) {
            return back()->withErrors([
                'member' => __('Link an approved member before depositing.'),
            ]);
        }

        $record->loadMissing('member');

        $validatedAmount = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'memo' => ['nullable', 'string', 'max:2000'],
        ]);

        $add = (int) round(((float) $validatedAmount['amount']) * 100);

        $memberPersonalAccountId = $this->ensureMemberPersonalChartAccount(
            $record,
            forLoan: false,
            actor: $request->user(),
        );

        $ledger = $this->validatedLedgerPosting(
            $request,
            $record->company_id,
            requireDebit: true,
            requireCredit: false,
        );
        $ledger['credit_chart_account_id'] = $memberPersonalAccountId;

        if ((int) $ledger['debit_chart_account_id'] === $memberPersonalAccountId) {
            return back()->withErrors([
                'debit_chart_account_id' => __('Cash / bank account must be different from the member personal account.'),
            ]);
        }
        $memo = __('Savings deposit :title', ['title' => $record->title]).$this->financeMemberMemoSuffix($record);
        if (! empty($validatedAmount['memo'])) {
            $memo .= ' — '.$validatedAmount['memo'];
        }

        $journalService = app(FinanceJournalPostingService::class);
        $postedJournalId = null;

        try {
            DB::transaction(function () use (
                $journalService,
                $request,
                $ledger,
                $add,
                $memo,
                $record,
                $validatedAmount,
                &$postedJournalId,
            ) {
                $entry = $journalService->postTwoLineJournal(
                    $record->company_id,
                    $request->user(),
                    $ledger['transaction_date'],
                    $memo,
                    $ledger['reference'] ?? null,
                    $add,
                    (int) $ledger['debit_chart_account_id'],
                    (int) $ledger['credit_chart_account_id'],
                    $record->member_id,
                    FinancialPosition::CATEGORY_SAVINGS,
                );
                $postedJournalId = (int) $entry->id;

                $record->increment('principal_cents', $add);
                $record->refresh();

                FinancialPositionMovement::query()->create([
                    'financial_position_id' => $record->id,
                    'company_id' => $record->company_id,
                    'user_id' => $request->user()->id,
                    'type' => FinancialPositionMovement::TYPE_DEPOSIT,
                    'amount_cents' => $add,
                    'balance_after_cents' => (int) $record->principal_cents,
                    'memo' => $validatedAmount['memo'] ?? null,
                    'journal_entry_id' => $entry->id,
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['ledger' => $e->getMessage()]);
        }

        return back()
            ->with('status', __('Deposit recorded and posted to the journal.'))
            ->with('posted_journal_id', $postedJournalId);
    }

    public function storeStructuredSavingsWithdrawal(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $category = $this->validatedCategory($category);
        abort_unless($category === FinancialPosition::CATEGORY_SAVINGS, 404);

        $record = $this->positionForCompany($request, $category, $position);
        $this->authorize('update', $record);
        $this->assertLoanOrSavingsProduct($record);

        if (! $record->usesStructuredSavingsFlow()) {
            return back()->withErrors([
                'flow' => __('This action applies to product-based savings only.'),
            ]);
        }

        if (! $record->isStructuredCatalogOperational()) {
            return back()->withErrors([
                'flow' => __('Approve this savings account before recording withdrawals.'),
            ]);
        }

        if (! $record->memberApprovedForFinance()) {
            return back()->withErrors([
                'member' => __('Link an approved member before withdrawing.'),
            ]);
        }

        $record->loadMissing('member');

        $validatedAmount = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'memo' => ['nullable', 'string', 'max:2000'],
        ]);

        $sub = (int) round(((float) $validatedAmount['amount']) * 100);

        if ($record->principal_cents < $sub) {
            return back()->withErrors([
                'amount' => __('Withdrawal exceeds current balance.'),
            ]);
        }

        $memberPersonalAccountId = $this->ensureMemberPersonalChartAccount(
            $record,
            forLoan: false,
            actor: $request->user(),
        );

        $ledger = $this->validatedLedgerPosting(
            $request,
            $record->company_id,
            requireDebit: false,
            requireCredit: true,
        );
        $ledger['debit_chart_account_id'] = $memberPersonalAccountId;

        if ((int) $ledger['credit_chart_account_id'] === $memberPersonalAccountId) {
            return back()->withErrors([
                'credit_chart_account_id' => __('Cash / bank account must be different from the member personal account.'),
            ]);
        }
        $memo = __('Savings withdrawal :title', ['title' => $record->title]).$this->financeMemberMemoSuffix($record);
        if (! empty($validatedAmount['memo'])) {
            $memo .= ' — '.$validatedAmount['memo'];
        }

        $journalService = app(FinanceJournalPostingService::class);
        $postedJournalId = null;

        try {
            DB::transaction(function () use (
                $journalService,
                $request,
                $ledger,
                $sub,
                $memo,
                $record,
                $validatedAmount,
                &$postedJournalId,
            ) {
                $entry = $journalService->postTwoLineJournal(
                    $record->company_id,
                    $request->user(),
                    $ledger['transaction_date'],
                    $memo,
                    $ledger['reference'] ?? null,
                    $sub,
                    (int) $ledger['debit_chart_account_id'],
                    (int) $ledger['credit_chart_account_id'],
                    $record->member_id,
                    FinancialPosition::CATEGORY_SAVINGS,
                );
                $postedJournalId = (int) $entry->id;

                $record->decrement('principal_cents', $sub);
                $record->refresh();

                FinancialPositionMovement::query()->create([
                    'financial_position_id' => $record->id,
                    'company_id' => $record->company_id,
                    'user_id' => $request->user()->id,
                    'type' => FinancialPositionMovement::TYPE_WITHDRAWAL,
                    'amount_cents' => -$sub,
                    'balance_after_cents' => (int) $record->principal_cents,
                    'memo' => $validatedAmount['memo'] ?? null,
                    'journal_entry_id' => $entry->id,
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['ledger' => $e->getMessage()]);
        }

        return back()
            ->with('status', __('Withdrawal recorded and posted to the journal.'))
            ->with('posted_journal_id', $postedJournalId);
    }

    public function storeStructuredSavingsAdjustment(Request $request, string $category, int $position): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $category = $this->validatedCategory($category);
        abort_unless($category === FinancialPosition::CATEGORY_SAVINGS, 404);

        $record = $this->positionForCompany($request, $category, $position);
        $this->authorize('update', $record);
        $this->assertLoanOrSavingsProduct($record);

        if (! $record->usesStructuredSavingsFlow()) {
            return back()->withErrors([
                'flow' => __('This action applies to product-based savings only.'),
            ]);
        }

        if (! $record->isStructuredCatalogOperational()) {
            return back()->withErrors([
                'flow' => __('Approve this savings account before adjustments.'),
            ]);
        }

        if (! $record->memberApprovedForFinance()) {
            return back()->withErrors([
                'member' => __('Link an approved member before adjusting.'),
            ]);
        }

        $record->loadMissing('member');

        $validatedAmount = $request->validate([
            'amount' => ['required', 'numeric'],
            'memo' => ['nullable', 'string', 'max:2000'],
        ]);

        $delta = (int) round(((float) $validatedAmount['amount']) * 100);
        if ($delta === 0) {
            return back()->withErrors([
                'amount' => __('Enter a non-zero adjustment amount.'),
            ]);
        }

        $new = (int) $record->principal_cents + $delta;

        if ($new < 0) {
            return back()->withErrors([
                'amount' => __('Resulting balance cannot be negative.'),
            ]);
        }

        $abs = abs($delta);
        $ledger = $this->validatedLedgerPosting($request, $record->company_id);

        $debitId = (int) $ledger['debit_chart_account_id'];
        $creditId = (int) $ledger['credit_chart_account_id'];
        if ($delta < 0) {
            [$debitId, $creditId] = [$creditId, $debitId];
        }

        $memo = __('Savings balance adjustment :title', ['title' => $record->title]).$this->financeMemberMemoSuffix($record);
        if (! empty($validatedAmount['memo'])) {
            $memo .= ' — '.$validatedAmount['memo'];
        }

        $journalService = app(FinanceJournalPostingService::class);

        try {
            DB::transaction(function () use (
                $journalService,
                $request,
                $ledger,
                $abs,
                $debitId,
                $creditId,
                $memo,
                $record,
                $validatedAmount,
                $delta,
                $new,
            ) {
                $entry = $journalService->postTwoLineJournal(
                    $record->company_id,
                    $request->user(),
                    $ledger['transaction_date'],
                    $memo,
                    $ledger['reference'] ?? null,
                    $abs,
                    $debitId,
                    $creditId,
                    $record->member_id,
                    FinancialPosition::CATEGORY_SAVINGS,
                );

                $record->update(['principal_cents' => $new]);

                FinancialPositionMovement::query()->create([
                    'financial_position_id' => $record->id,
                    'company_id' => $record->company_id,
                    'user_id' => $request->user()->id,
                    'type' => FinancialPositionMovement::TYPE_ADJUSTMENT,
                    'amount_cents' => $delta,
                    'balance_after_cents' => $new,
                    'memo' => $validatedAmount['memo'] ?? null,
                    'journal_entry_id' => $entry->id,
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['ledger' => $e->getMessage()]);
        }

        return back()->with('status', __('Adjustment recorded and posted to the journal.'));
    }

    private function validatedCategory(string $category): string
    {
        $allowed = FinancialPosition::categories();
        if (! in_array($category, $allowed, true)) {
            abort(404);
        }

        return $category;
    }

    private function categoryLabel(string $category): string
    {
        return match ($category) {
            FinancialPosition::CATEGORY_LOAN => __('Loans'),
            FinancialPosition::CATEGORY_INVESTMENT => __('Investments'),
            FinancialPosition::CATEGORY_SAVINGS => __('Savings'),
            default => $category,
        };
    }

    /**
     * @return array{title: string, principal: float|int|string, annual_interest_rate_percent: ?float, start_date: ?string, notes: ?string, member_id?: ?int, account_number?: ?string}
     */
    private function validatedPayload(Request $request, string $category, int $companyId, ?int $ignoreFinancialPositionId = null): array
    {
        $rateRules = $category === FinancialPosition::CATEGORY_INVESTMENT
            ? ['nullable', 'numeric', 'min:0', 'max:100']
            : ['required', 'numeric', 'min:0', 'max:100'];

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'principal' => ['required', 'numeric', 'min:0'],
            'annual_interest_rate_percent' => $rateRules,
            'start_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        $rules['member_id'] = [
            'required',
            'integer',
            Rule::exists('members', 'id')->where(
                fn ($q) => $q->where('company_id', $companyId)
                    ->where('status', Member::STATUS_APPROVED)
            ),
        ];

        if ($category === FinancialPosition::CATEGORY_LOAN) {
            $rules['loan_product_id'] = [
                'nullable',
                'integer',
                Rule::exists('loan_products', 'id')->where(
                    fn ($q) => $q->where('company_id', $companyId)->where('is_active', true)
                ),
            ];
            $rules['sanctioned_amount'] = ['nullable', 'numeric', 'min:0'];
        }

        if ($category === FinancialPosition::CATEGORY_SAVINGS) {
            $rules['savings_product_id'] = [
                'nullable',
                'integer',
                Rule::exists('savings_products', 'id')->where(
                    fn ($q) => $q->where('company_id', $companyId)->where('is_active', true)
                ),
            ];
        }

        if (in_array($category, [FinancialPosition::CATEGORY_LOAN, FinancialPosition::CATEGORY_SAVINGS], true)) {
            $unique = Rule::unique('financial_positions', 'account_number')->where(
                fn ($q) => $q->where('company_id', $companyId)
            );
            if ($ignoreFinancialPositionId !== null) {
                $unique->ignore($ignoreFinancialPositionId);
            }
            $rules['account_number'] = ['nullable', 'string', 'max:32', $unique];
        }

        return $request->validate($rules);
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function approvedMembersForSelect(int $companyId, string $category, ?FinancialPosition $forPosition = null): array
    {
        $list = Member::query()
            ->where('company_id', $companyId)
            ->where('status', Member::STATUS_APPROVED)
            ->orderBy('member_number')
            ->get()
            ->map(function (Member $m) {
                $tail = $m->reference_code
                    ? $m->name.' ('.$m->reference_code.')'
                    : $m->name;

                return [
                    'id' => $m->id,
                    'label' => '#'.$m->member_number.' — '.$tail,
                ];
            });

        $ids = $list->pluck('id')->all();

        if ($forPosition?->member_id && $forPosition->relationLoaded('member') && $forPosition->member !== null) {
            $m = $forPosition->member;
            if (! in_array((int) $m->id, array_map('intval', $ids), true)) {
                $tail = $m->reference_code
                    ? $m->name.' ('.$m->reference_code.')'
                    : $m->name;
                $suffix = $m->isApproved() ? '' : ' — '.__('not approved');
                $list->push([
                    'id' => $m->id,
                    'label' => '#'.$m->member_number.' — '.$tail.$suffix,
                ]);
            }
        }

        return $list->values()->all();
    }

    private function financeMemberMemoSuffix(FinancialPosition $record): string
    {
        if ($record->member === null) {
            return '';
        }

        return ' — '.__('Member').' #'.$record->member->member_number.': '.$record->member->name;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedLedgerPosting(
        Request $request,
        int $companyId,
        bool $requireDebit = true,
        bool $requireCredit = true,
    ): array {
        return $request->validate([
            'transaction_date' => ['required', 'date'],
            'debit_chart_account_id' => [
                $requireDebit ? 'required' : 'nullable',
                'integer',
                Rule::exists('chart_accounts', 'id')->where(
                    fn ($q) => $q->where('company_id', $companyId)
                        ->where('approval_status', ChartAccount::STATUS_APPROVED)
                ),
            ],
            'credit_chart_account_id' => [
                $requireCredit ? 'required' : 'nullable',
                'integer',
                'different:debit_chart_account_id',
                Rule::exists('chart_accounts', 'id')->where(
                    fn ($q) => $q->where('company_id', $companyId)
                        ->where('approval_status', ChartAccount::STATUS_APPROVED)
                ),
            ],
            'reference' => ['nullable', 'string', 'max:64'],
        ]);
    }

    private function ensureMemberPersonalChartAccount(FinancialPosition $record, bool $forLoan, User $actor): int
    {
        $accountCode = trim((string) ($record->account_number ?? ''));
        if ($accountCode === '') {
            throw new InvalidArgumentException(__('Missing account number for this member finance record.'));
        }

        $existing = ChartAccount::query()
            ->where('company_id', $record->company_id)
            ->where('code', $accountCode)
            ->first();

        if ($existing !== null) {
            if ($existing->approval_status !== ChartAccount::STATUS_APPROVED) {
                $existing->update([
                    'approval_status' => ChartAccount::STATUS_APPROVED,
                    'approved_at' => now(),
                    'approved_by_user_id' => $actor->id,
                    'approved_by_admin_id' => null,
                ]);
            }

            return (int) $existing->id;
        }

        $memberName = trim((string) ($record->member?->name ?? $record->title));
        $name = $forLoan
            ? 'Loan personal '.$memberName
            : 'Savings personal '.$memberName;

        $created = ChartAccount::query()->create([
            'company_id' => $record->company_id,
            'user_id' => $actor->id,
            'code' => $accountCode,
            'name' => $name,
            'type' => $forLoan ? ChartAccount::TYPE_ASSET : ChartAccount::TYPE_LIABILITY,
            'description' => __('Auto-created member personal account for finance posting.'),
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => $actor->id,
            'approved_by_admin_id' => null,
        ]);

        return (int) $created->id;
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function chartAccountOptions(int $companyId): array
    {
        return ChartAccount::query()
            ->where('company_id', $companyId)
            ->approvedForJournals()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (ChartAccount $a) => [
                'id' => $a->id,
                'label' => $a->code.' — '.$a->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function liabilityChartAccountOptions(int $companyId): array
    {
        return ChartAccount::query()
            ->where('company_id', $companyId)
            ->approvedForJournals()
            ->where('type', ChartAccount::TYPE_LIABILITY)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (ChartAccount $a) => [
                'id' => $a->id,
                'label' => $a->code.' — '.$a->name,
            ])
            ->all();
    }

    private function validateAdminCompanySelection(Request $request): void
    {
        if (! $request->user()->isAdmin()) {
            return;
        }

        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $request->session()->put('accounting_company_id', (int) $request->input('company_id'));
    }

    /**
     * @return array<string, mixed>
     */
    private function companyQuery(Request $request): array
    {
        if ($request->user()->isAdmin()) {
            return ['company_id' => $this->accountingCompany($request)->id];
        }

        return [];
    }

    /**
     * @return array<string, int|string>
     */
    private function financeIndexQuery(Request $request): array
    {
        $q = $this->companyQuery($request);
        $w = $request->query('workspace');
        if ($w === null || $w === '') {
            $w = $request->input('workspace');
        }
        if (in_array($w, ['front', 'back'], true)) {
            $q['workspace'] = $w;
        }

        return $q;
    }

    private function positionForCompany(Request $request, string $category, int $position): FinancialPosition
    {
        $category = $this->validatedCategory($category);
        $company = $this->accountingCompany($request);

        return FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', $category)
            ->findOrFail($position);
    }

    private function assertLoanOrSavingsProduct(FinancialPosition $record): void
    {
        if (! in_array($record->category, [
            FinancialPosition::CATEGORY_LOAN,
            FinancialPosition::CATEGORY_SAVINGS,
        ], true)) {
            abort(404);
        }
    }

    private function redirectIfStructuredCatalogNotOperational(
        FinancialPosition $record,
        string $errorKey,
        ?string $message = null,
    ): ?RedirectResponse {
        if ($record->usesStructuredCatalogWorkflow() && ! $record->isStructuredCatalogOperational()) {
            return back()->withErrors([
                $errorKey => $message ?? __('Approve this product-based account in the back office before continuing.'),
            ]);
        }

        return null;
    }

    private function structuredCatalogNotActiveJsonResponse(): JsonResponse
    {
        return response()->json([
            'message' => __('This account is not active yet.'),
        ], 422);
    }

    /**
     * @param  'deposit'|'withdraw'|'adjustment'  $kind
     */
    private function denyLegacyMovementForStructuredCatalog(
        FinancialPosition $record,
        string $kind,
    ): ?RedirectResponse {
        if ($record->category === FinancialPosition::CATEGORY_LOAN && $record->usesStructuredLoanFlow()) {
            $message = match ($kind) {
                'deposit' => __('For product-based loans, record disbursements with a journal entry from product actions.'),
                'withdraw' => __('For product-based loans, record principal repayments as installments with a journal entry.'),
                default => null,
            };

            return $message !== null ? back()->withErrors(['flow' => $message]) : null;
        }

        if ($record->category === FinancialPosition::CATEGORY_SAVINGS && $record->usesStructuredSavingsFlow()) {
            $message = match ($kind) {
                'deposit' => __('For product-based savings, record deposits with a journal entry from product actions.'),
                'withdraw' => __('For product-based savings, record withdrawals with a journal entry from product actions.'),
                'adjustment' => __('For product-based savings, record balance adjustments with a journal entry from product actions.'),
                default => null,
            };

            return $message !== null ? back()->withErrors(['flow' => $message]) : null;
        }

        return null;
    }

    private function finalizeCatalogApproval(FinancialPosition $record, bool $forLoan): RedirectResponse
    {
        $pending = $forLoan ? $record->isLoanPendingApproval() : $record->isSavingsPendingApproval();
        if (! $pending) {
            return back()->withErrors([
                'workflow' => $forLoan
                    ? __('Only pending loan applications can be approved.')
                    : __('Only pending savings applications can be approved.'),
            ]);
        }

        if ($forLoan) {
            $record->load('loanProduct');
            $acct = $record->proposedAccountNumber();
        } else {
            $record->load('savingsProduct');
            $acct = $record->proposedSavingsAccountNumber();
        }

        if ($acct === null || $acct === '') {
            return back()->withErrors([
                'workflow' => __('Cannot assign account number — missing product or sequence.'),
            ]);
        }

        $dup = FinancialPosition::query()
            ->where('company_id', $record->company_id)
            ->where('account_number', $acct)
            ->where('id', '!=', $record->id)
            ->exists();

        if ($dup) {
            return back()->withErrors([
                'workflow' => __('That account number is already in use.'),
            ]);
        }

        if ($forLoan) {
            $record->update([
                'loan_workflow_status' => FinancialPosition::LOAN_WORKFLOW_ACTIVE,
                'account_number' => $acct,
            ]);

            return back()->with('status', __('Loan account approved. The account number is active for disbursements, repayments, and lookups.'));
        }

        $record->update([
            'savings_workflow_status' => FinancialPosition::LOAN_WORKFLOW_ACTIVE,
            'account_number' => $acct,
        ]);

        return back()->with('status', __('Savings account approved. The account number is active for deposits, withdrawals, and lookups.'));
    }

    private function finalizeCatalogRejection(FinancialPosition $record, bool $forLoan): RedirectResponse
    {
        $pending = $forLoan ? $record->isLoanPendingApproval() : $record->isSavingsPendingApproval();
        if (! $pending) {
            return back()->withErrors([
                'workflow' => $forLoan
                    ? __('Only pending loan applications can be rejected.')
                    : __('Only pending savings applications can be rejected.'),
            ]);
        }

        if ($forLoan) {
            $record->update([
                'loan_workflow_status' => FinancialPosition::LOAN_WORKFLOW_REJECTED,
                'account_number' => null,
            ]);

            return back()->with('status', __('Loan application rejected.'));
        }

        $record->update([
            'savings_workflow_status' => FinancialPosition::LOAN_WORKFLOW_REJECTED,
            'account_number' => null,
        ]);

        return back()->with('status', __('Savings application rejected.'));
    }

    private function movementTypeLabel(string $type): string
    {
        return match ($type) {
            FinancialPositionMovement::TYPE_OPENING => __('Opening balance'),
            FinancialPositionMovement::TYPE_DEPOSIT => __('Deposit'),
            FinancialPositionMovement::TYPE_WITHDRAWAL => __('Withdrawal'),
            FinancialPositionMovement::TYPE_ADJUSTMENT => __('Adjustment'),
            FinancialPositionMovement::TYPE_DISBURSEMENT => __('Loan disbursement'),
            FinancialPositionMovement::TYPE_INSTALLMENT => __('Installment / principal repayment'),
            FinancialPositionMovement::TYPE_PENALTY => __('Penalty / late charge'),
            default => $type,
        };
    }
}
