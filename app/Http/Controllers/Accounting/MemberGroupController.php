<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MemberGroupController extends Controller
{
    use ResolvesAccountingCompany;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Member::class);
        $company = $this->accountingCompany($request);

        $groups = MemberGroup::query()
            ->where('company_id', $company->id)
            ->withCount('members')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (MemberGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'code' => $group->code,
                'notes' => $group->notes,
                'members_count' => $group->members_count,
            ]);

        $approvedMembers = Member::query()
            ->where('company_id', $company->id)
            ->where('status', Member::STATUS_APPROVED)
            ->orderBy('name')
            ->get(['id', 'name', 'member_number', 'reference_code'])
            ->map(fn (Member $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'member_number' => $m->member_number,
                'reference_code' => $m->reference_code,
            ])->all();

        return Inertia::render('Accounting/Members/Groups/Index', [
            'groups' => $groups,
            'approvedMembers' => $approvedMembers,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Member::class);
        $this->validateAdminCompanySelection($request);
        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('member_groups', 'code')->where(
                    fn ($q) => $q->where('company_id', $company->id)
                ),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'distinct'],
        ]);

        $memberIds = collect($validated['member_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $allowedMemberIds = Member::query()
            ->where('company_id', $company->id)
            ->where('status', Member::STATUS_APPROVED)
            ->whereIn('id', $memberIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $group = MemberGroup::query()->create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $group->members()->sync($allowedMemberIds);

        return redirect()->route('member-groups.show', [
            'group' => $group->id,
            ...$this->companyQuery($request),
        ])->with('status', __('Member group created.'));
    }

    public function show(Request $request, int $group): Response
    {
        $this->authorize('viewAny', Member::class);
        $company = $this->accountingCompany($request);

        $record = MemberGroup::query()
            ->where('company_id', $company->id)
            ->with(['members' => fn ($q) => $q->orderBy('name')])
            ->findOrFail($group);

        $approvedMembers = Member::query()
            ->where('company_id', $company->id)
            ->where('status', Member::STATUS_APPROVED)
            ->orderBy('name')
            ->get(['id', 'name', 'member_number', 'reference_code'])
            ->map(fn (Member $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'member_number' => $m->member_number,
                'reference_code' => $m->reference_code,
            ])->all();

        return Inertia::render('Accounting/Members/Groups/Show', [
            'group' => [
                'id' => $record->id,
                'name' => $record->name,
                'code' => $record->code,
                'notes' => $record->notes,
                'member_ids' => $record->members->pluck('id')->all(),
                'members' => $record->members->map(fn (Member $m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'member_number' => $m->member_number,
                    'reference_code' => $m->reference_code,
                    'email' => $m->email,
                    'phone' => $m->phone,
                ])->all(),
            ],
            'approvedMembers' => $approvedMembers,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'canManage' => $request->user()->can('create', Member::class),
        ]);
    }

    public function syncMembers(Request $request, int $group): RedirectResponse
    {
        $this->authorize('create', Member::class);
        $this->validateAdminCompanySelection($request);
        $company = $this->accountingCompany($request);

        $record = MemberGroup::query()
            ->where('company_id', $company->id)
            ->findOrFail($group);

        $validated = $request->validate([
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'distinct'],
        ]);

        $memberIds = collect($validated['member_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $allowedMemberIds = Member::query()
            ->where('company_id', $company->id)
            ->where('status', Member::STATUS_APPROVED)
            ->whereIn('id', $memberIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $record->members()->sync($allowedMemberIds);

        return back()->with('status', __('Group members updated.'));
    }

    private function validateAdminCompanySelection(Request $request): void
    {
        if (! $request->user()?->isAdmin()) {
            return;
        }

        $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
        ]);
    }

    private function companyQuery(Request $request): array
    {
        if (! $request->user()?->isAdmin()) {
            return [];
        }

        $companyId = (int) $request->input('company_id', 0);

        return $companyId > 0 ? ['company_id' => $companyId] : [];
    }
}
