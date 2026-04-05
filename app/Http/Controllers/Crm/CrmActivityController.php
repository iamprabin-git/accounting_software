<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Concerns\ValidatesAdminCompanyForInertiaPosts;
use App\Http\Controllers\Crm\Concerns\MapsCrmSubjects;
use App\Models\CrmAccount;
use App\Models\CrmActivity;
use App\Models\CrmContact;
use App\Models\CrmOpportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CrmActivityController extends Controller
{
    use MapsCrmSubjects;
    use ResolvesAccountingCompany;
    use ValidatesAdminCompanyForInertiaPosts;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CrmActivity::class);

        $company = $this->accountingCompany($request);

        $filter = $request->string('filter', 'open')->toString();
        if (! in_array($filter, ['open', 'done', 'all'], true)) {
            $filter = 'open';
        }

        $q = CrmActivity::query()
            ->where('company_id', $company->id)
            ->with('subject');

        if ($filter === 'open') {
            $q->whereNull('completed_at');
        } elseif ($filter === 'done') {
            $q->whereNotNull('completed_at');
        }
        // filter === 'all' → no extra constraint

        $activities = $q->orderByDesc('due_at')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (CrmActivity $a) => [
                'id' => $a->id,
                'type' => $a->type,
                'type_label' => CrmActivity::typeLabels()[$a->type] ?? $a->type,
                'title' => $a->title,
                'body' => $a->body,
                'due_at' => $a->due_at?->toIso8601String(),
                'completed_at' => $a->completed_at?->toIso8601String(),
                'subject_type' => $a->subject_type,
                'subject_label' => $this->crmSubjectLabel($a->subject),
            ]);

        return Inertia::render('Crm/Activities/Index', [
            'activities' => $activities,
            'filter' => $filter,
            'typeLabels' => CrmActivity::typeLabels(),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', CrmActivity::class);

        $company = $this->accountingCompany($request);

        return Inertia::render('Crm/Activities/Create', [
            'accounts' => CrmAccount::query()->where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'contacts' => CrmContact::query()->where('company_id', $company->id)->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'opportunities' => CrmOpportunity::query()->where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'typeLabels' => CrmActivity::typeLabels(),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CrmActivity::class);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(CrmActivity::typeLabels()))],
            'subject_kind' => ['required', 'string', Rule::in(['crm_account', 'crm_contact', 'crm_opportunity'])],
            'subject_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'due_at' => ['nullable', 'date'],
        ]);

        $this->assertSubjectBelongsToCompany(
            $validated['subject_kind'],
            (int) $validated['subject_id'],
            $company->id,
        );

        CrmActivity::query()->create([
            'company_id' => $company->id,
            'type' => $validated['type'],
            'subject_type' => $validated['subject_kind'],
            'subject_id' => (int) $validated['subject_id'],
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
            'created_by_user_id' => $request->user()?->id,
        ]);

        return redirect()->route('crm.activities.index', $this->companyQuery($request))
            ->with('status', __('Activity logged.'));
    }

    public function edit(Request $request, CrmActivity $activity): Response
    {
        $this->authorize('update', $activity);

        $company = $this->accountingCompany($request);
        abort_unless($activity->company_id === $company->id, 404);

        $activity->load('subject');

        return Inertia::render('Crm/Activities/Edit', [
            'activity' => [
                'id' => $activity->id,
                'type' => $activity->type,
                'subject_kind' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'subject_label' => $this->crmSubjectLabel($activity->subject),
                'title' => $activity->title,
                'body' => $activity->body,
                'due_at' => $activity->due_at?->format('Y-m-d\TH:i'),
            ],
            'typeLabels' => CrmActivity::typeLabels(),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function update(Request $request, CrmActivity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);
        abort_unless($activity->company_id === $company->id, 404);

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(CrmActivity::typeLabels()))],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'due_at' => ['nullable', 'date'],
        ]);

        $activity->update([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
        ]);

        return redirect()->route('crm.activities.index', $this->companyQuery($request))
            ->with('status', __('Activity updated.'));
    }

    public function complete(Request $request, CrmActivity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);
        abort_unless($activity->company_id === $company->id, 404);

        $activity->update(['completed_at' => now()]);

        return back()->with('status', __('Marked complete.'));
    }

    public function destroy(Request $request, CrmActivity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);
        abort_unless($activity->company_id === $company->id, 404);

        $activity->delete();

        return redirect()->route('crm.activities.index', $this->companyQuery($request))
            ->with('status', __('Activity removed.'));
    }

    protected function assertSubjectBelongsToCompany(string $kind, int $subjectId, int $companyId): void
    {
        $ok = match ($kind) {
            'crm_account' => CrmAccount::query()->where('company_id', $companyId)->whereKey($subjectId)->exists(),
            'crm_contact' => CrmContact::query()->where('company_id', $companyId)->whereKey($subjectId)->exists(),
            'crm_opportunity' => CrmOpportunity::query()->where('company_id', $companyId)->whereKey($subjectId)->exists(),
            default => false,
        };

        abort_unless($ok, 422, 'Invalid related record.');
    }
}
