<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Concerns\ValidatesAdminCompanyForInertiaPosts;
use App\Models\CrmAccount;
use App\Models\CrmContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CrmContactController extends Controller
{
    use ResolvesAccountingCompany;
    use ValidatesAdminCompanyForInertiaPosts;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CrmContact::class);

        $company = $this->accountingCompany($request);

        $contacts = CrmContact::query()
            ->where('company_id', $company->id)
            ->with('account:id,name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (CrmContact $c) => [
                'id' => $c->id,
                'name' => $c->fullName(),
                'email' => $c->email,
                'phone' => $c->phone,
                'job_title' => $c->job_title,
                'account_name' => $c->account?->name,
            ]);

        return Inertia::render('Crm/Contacts/Index', [
            'contacts' => $contacts,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', CrmContact::class);

        $company = $this->accountingCompany($request);

        $accounts = CrmAccount::query()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Crm/Contacts/Create', [
            'accounts' => $accounts,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CrmContact::class);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'crm_account_id' => [
                'nullable',
                'integer',
                Rule::exists('crm_accounts', 'id')->where('company_id', $company->id),
            ],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        CrmContact::query()->create([
            'company_id' => $company->id,
            ...$validated,
        ]);

        return redirect()->route('crm.contacts.index', $this->companyQuery($request))
            ->with('status', __('Contact created.'));
    }

    public function edit(Request $request, CrmContact $contact): Response
    {
        $this->authorize('update', $contact);

        $company = $this->accountingCompany($request);
        abort_unless($contact->company_id === $company->id, 404);

        $accounts = CrmAccount::query()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Crm/Contacts/Edit', [
            'contact' => [
                'id' => $contact->id,
                'crm_account_id' => $contact->crm_account_id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'job_title' => $contact->job_title,
                'notes' => $contact->notes,
            ],
            'accounts' => $accounts,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function update(Request $request, CrmContact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);
        abort_unless($contact->company_id === $company->id, 404);

        $validated = $request->validate([
            'crm_account_id' => [
                'nullable',
                'integer',
                Rule::exists('crm_accounts', 'id')->where('company_id', $company->id),
            ],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $contact->update($validated);

        return redirect()->route('crm.contacts.index', $this->companyQuery($request))
            ->with('status', __('Contact updated.'));
    }

    public function destroy(Request $request, CrmContact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);
        abort_unless($contact->company_id === $company->id, 404);

        $contact->delete();

        return redirect()->route('crm.contacts.index', $this->companyQuery($request))
            ->with('status', __('Contact removed.'));
    }
}
