<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\Member;
use App\Support\EmailAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    use ResolvesAccountingCompany;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Member::class);

        $company = $this->accountingCompany($request);

        $members = Member::query()
            ->where('company_id', $company->id)
            ->with(['createdBy:id,name', 'approvedBy:id,name'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderBy('member_number')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Member $m) => [
                'id' => $m->id,
                'member_number' => $m->member_number,
                'reference_code' => $m->reference_code,
                'name' => $m->name,
                'email' => $m->email,
                'phone' => $m->phone,
                'status' => $m->status,
                'created_by_name' => $m->createdBy?->name,
                'approved_by_name' => $m->approvedBy?->name,
                'approved_at' => $m->approved_at?->toIso8601String(),
            ]);

        return Inertia::render('Accounting/Members/Index', [
            'members' => $members,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'can_create' => $request->user()->can('create', Member::class),
            'can_approve' => $request->user()->isCompany() || $request->user()->isAdmin(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Member::class);

        $company = $this->accountingCompany($request);

        return Inertia::render('Accounting/Members/Create', [
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Member::class);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $request->merge([
            'email' => EmailAddress::normalize($request->input('email')),
        ]);

        $validated = $request->validate([
            'reference_code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('members', 'reference_code')->where(
                    fn ($q) => $q->where('company_id', $company->id)
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255', EmailAddress::laravelRule()],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Member::query()->create([
            'company_id' => $company->id,
            'reference_code' => $validated['reference_code'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => Member::STATUS_PENDING,
            'created_by_user_id' => $request->user()->id,
        ]);

        return redirect()->route('members.index', $this->companyQuery($request))
            ->with('status', __('Member submitted for company approval.'));
    }

    public function edit(Request $request, int $member): Response
    {
        $company = $this->accountingCompany($request);

        $record = Member::query()
            ->where('company_id', $company->id)
            ->findOrFail($member);

        $this->authorize('update', $record);

        return Inertia::render('Accounting/Members/Edit', [
            'member' => [
                'id' => $record->id,
                'member_number' => $record->member_number,
                'reference_code' => $record->reference_code ?? '',
                'name' => $record->name,
                'email' => $record->email ?? '',
                'phone' => $record->phone ?? '',
                'address' => $record->address ?? '',
                'notes' => $record->notes ?? '',
                'status' => $record->status,
            ],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function update(Request $request, int $member): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = Member::query()
            ->where('company_id', $company->id)
            ->findOrFail($member);

        $this->authorize('update', $record);

        $request->merge([
            'email' => EmailAddress::normalize($request->input('email')),
        ]);

        $validated = $request->validate([
            'reference_code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('members', 'reference_code')
                    ->where(fn ($q) => $q->where('company_id', $company->id))
                    ->ignore($record->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255', EmailAddress::laravelRule()],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $record->update([
            'reference_code' => $validated['reference_code'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('members.index', $this->companyQuery($request))
            ->with('status', __('Member updated.'));
    }

    public function approve(Request $request, int $member): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = Member::query()
            ->where('company_id', $company->id)
            ->findOrFail($member);

        $this->authorize('approve', $record);

        if (! $record->isPending()) {
            return back()->withErrors(['status' => __('Only pending members can be approved.')]);
        }

        $record->update([
            'status' => Member::STATUS_APPROVED,
            'approved_by_user_id' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return back()->with('status', __('Member approved. Loan and savings can be linked to this member.'));
    }

    public function reject(Request $request, int $member): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = Member::query()
            ->where('company_id', $company->id)
            ->findOrFail($member);

        $this->authorize('reject', $record);

        if (! $record->isPending()) {
            return back()->withErrors(['status' => __('Only pending members can be rejected.')]);
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $record->update([
            'status' => Member::STATUS_REJECTED,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return back()->with('status', __('Member rejected.'));
    }

    public function destroy(Request $request, int $member): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = Member::query()
            ->where('company_id', $company->id)
            ->findOrFail($member);

        $this->authorize('delete', $record);

        if ($record->financialPositions()->exists()) {
            return back()->withErrors([
                'delete' => __('Remove linked loan/savings positions before deleting this member.'),
            ]);
        }

        $record->delete();

        return redirect()->route('members.index', $this->companyQuery($request))
            ->with('status', __('Member removed.'));
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
}
