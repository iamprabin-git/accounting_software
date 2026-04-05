<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\ChartAccount;
use App\Models\ChartAccountTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ChartAccountController extends Controller
{
    use ResolvesAccountingCompany;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ChartAccount::class);

        $company = $this->accountingCompany($request);

        $accounts = ChartAccount::query()
            ->where('company_id', $company->id)
            ->with(['user:id,name', 'template:id,code'])
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ChartAccount $a) => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'type' => $a->type,
                'description' => $a->description,
                'creator_name' => $a->user?->name,
                'approval_status' => $a->approval_status,
                'template_code' => $a->template?->code,
            ]);

        return Inertia::render('Accounting/ChartAccounts/Index', [
            'accounts' => $accounts,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'canApproveChartAccounts' => $request->user()->canApproveChartAccounts(),
            'canManageChartAccounts' => $request->user()->canManageChartOfAccounts(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ChartAccount::class);

        $company = $this->accountingCompany($request);

        return Inertia::render('Accounting/ChartAccounts/Create', [
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'accountTypes' => $this->accountTypeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ChartAccount::class);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('chart_accounts', 'code')->where(
                    fn ($q) => $q->where('company_id', $company->id)
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(array_keys($this->accountTypeOptions()))],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $autoApprove = $user->isCompany() || $user->isAdmin();

        ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'chart_account_template_id' => null,
            'approval_status' => $autoApprove ? ChartAccount::STATUS_APPROVED : ChartAccount::STATUS_PENDING,
            'approved_at' => $autoApprove ? now() : null,
            'approved_by_user_id' => $autoApprove ? $user->id : null,
        ]);

        return redirect()->route('chart-accounts.index', $this->companyQuery($request))
            ->with(
                'status',
                $autoApprove
                    ? __('Account created.')
                    : __('Account submitted for company approval before it can be used in journals.')
            );
    }

    public function edit(Request $request, int $account): Response
    {
        $company = $this->accountingCompany($request);

        $chartAccount = ChartAccount::query()
            ->where('company_id', $company->id)
            ->findOrFail($account);

        $this->authorize('update', $chartAccount);

        return Inertia::render('Accounting/ChartAccounts/Edit', [
            'account' => [
                'id' => $chartAccount->id,
                'code' => $chartAccount->code,
                'name' => $chartAccount->name,
                'type' => $chartAccount->type,
                'description' => $chartAccount->description,
            ],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'accountTypes' => $this->accountTypeOptions(),
        ]);
    }

    public function update(Request $request, int $account): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $chartAccount = ChartAccount::query()
            ->where('company_id', $company->id)
            ->findOrFail($account);

        $this->authorize('update', $chartAccount);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('chart_accounts', 'code')
                    ->where(fn ($q) => $q->where('company_id', $company->id))
                    ->ignore($chartAccount->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(array_keys($this->accountTypeOptions()))],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $chartAccount->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('chart-accounts.index', $this->companyQuery($request))
            ->with('status', __('Account updated.'));
    }

    public function destroy(Request $request, int $account): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $chartAccount = ChartAccount::query()
            ->where('company_id', $company->id)
            ->findOrFail($account);

        $this->authorize('delete', $chartAccount);

        if ($company->inventory_chart_account_id === $chartAccount->id) {
            $company->inventory_chart_account_id = null;
            $company->save();
        }

        if ($chartAccount->journalLines()->exists()) {
            return redirect()->route('chart-accounts.index', $this->companyQuery($request))
                ->withErrors([
                    'delete' => __('This account cannot be deleted while it is used on journal lines.'),
                ]);
        }

        $chartAccount->delete();

        return redirect()->route('chart-accounts.index', $this->companyQuery($request))
            ->with('status', __('Account deleted.'));
    }

    public function catalog(Request $request): Response
    {
        $this->authorize('viewAny', ChartAccount::class);

        $company = $this->accountingCompany($request);

        $adoptedIds = ChartAccount::query()
            ->where('company_id', $company->id)
            ->whereNotNull('chart_account_template_id')
            ->pluck('chart_account_template_id');

        $templates = ChartAccountTemplate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'description']);

        $available = $templates->whereNotIn('id', $adoptedIds)->values()->map(fn (ChartAccountTemplate $t) => [
            'id' => $t->id,
            'code' => $t->code,
            'name' => $t->name,
            'type' => $t->type,
            'description' => $t->description,
        ])->all();

        return Inertia::render('Accounting/ChartAccounts/Catalog', [
            'templates' => $available,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function storeFromTemplate(Request $request): RedirectResponse
    {
        $this->authorize('create', ChartAccount::class);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'chart_account_template_id' => ['required', 'integer', 'exists:chart_account_templates,id'],
        ]);

        $template = ChartAccountTemplate::query()
            ->where('is_active', true)
            ->findOrFail($validated['chart_account_template_id']);

        $already = ChartAccount::query()
            ->where('company_id', $company->id)
            ->where('chart_account_template_id', $template->id)
            ->exists();

        if ($already) {
            return redirect()->route('chart-accounts.catalog', $this->companyQuery($request))
                ->with('status', __('That standard account is already in your chart.'));
        }

        $codeTaken = ChartAccount::query()
            ->where('company_id', $company->id)
            ->where('code', $template->code)
            ->exists();

        if ($codeTaken) {
            return redirect()->route('chart-accounts.catalog', $this->companyQuery($request))
                ->withErrors([
                    'chart_account_template_id' => __('You already use this account code. Remove or rename the existing account first, or ask an admin for help.'),
                ]);
        }

        $actor = $request->user();

        ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $actor->id,
            'chart_account_template_id' => $template->id,
            'code' => $template->code,
            'name' => $template->name,
            'type' => $template->type,
            'description' => $template->description,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => $actor->id,
        ]);

        return redirect()->route('chart-accounts.index', $this->companyQuery($request))
            ->with('status', __('Standard account added to your chart.'));
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
     * @return array<string, string>
     */
    private function accountTypeOptions(): array
    {
        return [
            ChartAccount::TYPE_ASSET => __('Asset'),
            ChartAccount::TYPE_LIABILITY => __('Liability'),
            ChartAccount::TYPE_EQUITY => __('Equity'),
            ChartAccount::TYPE_REVENUE => __('Revenue'),
            ChartAccount::TYPE_EXPENSE => __('Expense'),
        ];
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
}
