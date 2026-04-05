<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Concerns\ValidatesAdminCompanyForInertiaPosts;
use App\Models\CrmAccount;
use App\Models\CrmContact;
use App\Models\CrmOpportunity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CrmOpportunityController extends Controller
{
    use ResolvesAccountingCompany;
    use ValidatesAdminCompanyForInertiaPosts;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CrmOpportunity::class);

        $company = $this->accountingCompany($request);

        $opportunities = CrmOpportunity::query()
            ->where('company_id', $company->id)
            ->with(['account:id,name', 'contact:id,first_name,last_name', 'owner:id,name'])
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (CrmOpportunity $o) => [
                'id' => $o->id,
                'name' => $o->name,
                'stage' => $o->stage,
                'amount_cents' => $o->amount_cents !== null ? (int) $o->amount_cents : null,
                'probability' => $o->probability,
                'expected_close_date' => $o->expected_close_date?->toDateString(),
                'account_name' => $o->account?->name,
                'contact_name' => $o->contact ? $o->contact->fullName() : null,
                'owner_name' => $o->owner?->name,
            ]);

        return Inertia::render('Crm/Opportunities/Index', [
            'opportunities' => $opportunities,
            'stageLabels' => CrmOpportunity::stageLabels(),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', CrmOpportunity::class);

        $company = $this->accountingCompany($request);

        return Inertia::render('Crm/Opportunities/Create', [
            'accounts' => CrmAccount::query()->where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'contacts' => CrmContact::query()->where('company_id', $company->id)->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'crm_account_id']),
            'owners' => User::query()
                ->where('company_id', $company->id)
                ->whereIn('role', [User::ROLE_COMPANY, User::ROLE_STAFF])
                ->orderBy('name')
                ->get(['id', 'name']),
            'stageLabels' => CrmOpportunity::stageLabels(),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CrmOpportunity::class);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'stage' => ['required', 'string', Rule::in(array_keys(CrmOpportunity::stageLabels()))],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'crm_account_id' => [
                'nullable',
                'integer',
                Rule::exists('crm_accounts', 'id')->where('company_id', $company->id),
            ],
            'crm_contact_id' => [
                'nullable',
                'integer',
                Rule::exists('crm_contacts', 'id')->where('company_id', $company->id),
            ],
            'owner_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $company->id)),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $amountCents = isset($validated['amount']) && $validated['amount'] !== ''
            ? (int) round((float) $validated['amount'] * 100)
            : null;

        CrmOpportunity::query()->create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'stage' => $validated['stage'],
            'amount_cents' => $amountCents,
            'probability' => $validated['probability'] ?? null,
            'expected_close_date' => $validated['expected_close_date'] ?? null,
            'crm_account_id' => $validated['crm_account_id'] ?? null,
            'crm_contact_id' => $validated['crm_contact_id'] ?? null,
            'owner_user_id' => $validated['owner_user_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('crm.opportunities.index', $this->companyQuery($request))
            ->with('status', __('Opportunity created.'));
    }

    public function edit(Request $request, CrmOpportunity $opportunity): Response
    {
        $this->authorize('update', $opportunity);

        $company = $this->accountingCompany($request);
        abort_unless($opportunity->company_id === $company->id, 404);

        return Inertia::render('Crm/Opportunities/Edit', [
            'opportunity' => [
                'id' => $opportunity->id,
                'name' => $opportunity->name,
                'stage' => $opportunity->stage,
                'amount' => $opportunity->amount_cents !== null ? $opportunity->amount_cents / 100 : '',
                'probability' => $opportunity->probability,
                'expected_close_date' => $opportunity->expected_close_date?->toDateString(),
                'crm_account_id' => $opportunity->crm_account_id,
                'crm_contact_id' => $opportunity->crm_contact_id,
                'owner_user_id' => $opportunity->owner_user_id,
                'notes' => $opportunity->notes,
            ],
            'accounts' => CrmAccount::query()->where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'contacts' => CrmContact::query()->where('company_id', $company->id)->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'crm_account_id']),
            'owners' => User::query()
                ->where('company_id', $company->id)
                ->whereIn('role', [User::ROLE_COMPANY, User::ROLE_STAFF])
                ->orderBy('name')
                ->get(['id', 'name']),
            'stageLabels' => CrmOpportunity::stageLabels(),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function update(Request $request, CrmOpportunity $opportunity): RedirectResponse
    {
        $this->authorize('update', $opportunity);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);
        abort_unless($opportunity->company_id === $company->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'stage' => ['required', 'string', Rule::in(array_keys(CrmOpportunity::stageLabels()))],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'crm_account_id' => [
                'nullable',
                'integer',
                Rule::exists('crm_accounts', 'id')->where('company_id', $company->id),
            ],
            'crm_contact_id' => [
                'nullable',
                'integer',
                Rule::exists('crm_contacts', 'id')->where('company_id', $company->id),
            ],
            'owner_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $company->id)),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $amountCents = array_key_exists('amount', $validated) && $validated['amount'] !== '' && $validated['amount'] !== null
            ? (int) round((float) $validated['amount'] * 100)
            : null;

        $opportunity->update([
            'name' => $validated['name'],
            'stage' => $validated['stage'],
            'amount_cents' => $amountCents,
            'probability' => $validated['probability'] ?? null,
            'expected_close_date' => $validated['expected_close_date'] ?? null,
            'crm_account_id' => $validated['crm_account_id'] ?? null,
            'crm_contact_id' => $validated['crm_contact_id'] ?? null,
            'owner_user_id' => $validated['owner_user_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('crm.opportunities.index', $this->companyQuery($request))
            ->with('status', __('Opportunity updated.'));
    }

    public function destroy(Request $request, CrmOpportunity $opportunity): RedirectResponse
    {
        $this->authorize('delete', $opportunity);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);
        abort_unless($opportunity->company_id === $company->id, 404);

        $opportunity->delete();

        return redirect()->route('crm.opportunities.index', $this->companyQuery($request))
            ->with('status', __('Opportunity removed.'));
    }
}
