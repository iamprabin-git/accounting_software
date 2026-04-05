<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Concerns\ValidatesAdminCompanyForInertiaPosts;
use App\Models\CrmAccount;
use App\Models\CrmOpportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrmAccountController extends Controller
{
    use ResolvesAccountingCompany;
    use ValidatesAdminCompanyForInertiaPosts;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CrmAccount::class);

        $company = $this->accountingCompany($request);

        $accounts = CrmAccount::query()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (CrmAccount $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'industry' => $a->industry,
                'email' => $a->email,
                'phone' => $a->phone,
            ]);

        return Inertia::render('Crm/Accounts/Index', [
            'accounts' => $accounts,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', CrmAccount::class);

        $company = $this->accountingCompany($request);

        return Inertia::render('Crm/Accounts/Create', [
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CrmAccount::class);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        CrmAccount::query()->create([
            'company_id' => $company->id,
            ...$validated,
        ]);

        return redirect()->route('crm.accounts.index', $this->companyQuery($request))
            ->with('status', __('Account created.'));
    }

    public function show(Request $request, CrmAccount $account): Response
    {
        $this->authorize('view', $account);

        $company = $this->accountingCompany($request);
        abort_unless($account->company_id === $company->id, 404);

        $account->load([
            'contacts' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name'),
            'opportunities' => fn ($q) => $q->orderByDesc('updated_at')->limit(20),
        ]);

        return Inertia::render('Crm/Accounts/Show', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'industry' => $account->industry,
                'website' => $account->website,
                'phone' => $account->phone,
                'email' => $account->email,
                'address' => $account->address,
                'notes' => $account->notes,
                'contacts' => $account->contacts->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->fullName(),
                    'email' => $c->email,
                    'phone' => $c->phone,
                    'job_title' => $c->job_title,
                ]),
                'opportunities' => $account->opportunities->map(fn ($o) => [
                    'id' => $o->id,
                    'name' => $o->name,
                    'stage' => $o->stage,
                    'amount_cents' => $o->amount_cents !== null ? (int) $o->amount_cents : null,
                ]),
            ],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'stageLabels' => CrmOpportunity::stageLabels(),
        ]);
    }

    public function edit(Request $request, CrmAccount $account): Response
    {
        $this->authorize('update', $account);

        $company = $this->accountingCompany($request);
        abort_unless($account->company_id === $company->id, 404);

        return Inertia::render('Crm/Accounts/Edit', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'industry' => $account->industry,
                'website' => $account->website,
                'phone' => $account->phone,
                'email' => $account->email,
                'address' => $account->address,
                'notes' => $account->notes,
            ],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function update(Request $request, CrmAccount $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);
        abort_unless($account->company_id === $company->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $account->update($validated);

        return redirect()->route('crm.accounts.show', array_merge(
            ['account' => $account->id],
            $this->companyQuery($request),
        ))->with('status', __('Account updated.'));
    }

    public function destroy(Request $request, CrmAccount $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);
        abort_unless($account->company_id === $company->id, 404);

        $account->delete();

        return redirect()->route('crm.accounts.index', $this->companyQuery($request))
            ->with('status', __('Account removed.'));
    }
}
