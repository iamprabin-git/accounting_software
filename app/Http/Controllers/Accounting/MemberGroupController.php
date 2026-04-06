<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\FinancialPosition;
use App\Models\FinancialPositionMovement;
use App\Models\GroupDepositBatch;
use App\Models\GroupDepositBatchLine;
use App\Models\GroupLoanCollectionBatch;
use App\Models\GroupLoanCollectionBatchLine;
use App\Models\Member;
use App\Models\MemberGroup;
use App\Models\MemberGroupMember;
use App\Models\User;
use App\Services\AccountingAuditService;
use App\Services\FinanceJournalPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class MemberGroupController extends Controller
{
    use ResolvesAccountingCompany;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Member::class);
        $company = $this->accountingCompany($request);

        $groups = MemberGroup::query()
            ->where('company_id', $company->id)
            ->withCount([
                'members as active_members_count' => fn ($q) => $q->whereNull('left_at'),
                'depositBatches as deposit_batches_count',
            ])
            ->with('createdBy:id,name')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (MemberGroup $g) => [
                'id' => $g->id,
                'code' => $g->code,
                'name' => $g->name,
                'meeting_day' => $g->meeting_day,
                'status' => $g->status,
                'active_members_count' => $g->active_members_count,
                'deposit_batches_count' => $g->deposit_batches_count,
                'created_by_name' => $g->createdBy?->name,
            ]);

        $eligibleMembers = Member::query()
            ->where('company_id', $company->id)
            ->where('status', Member::STATUS_APPROVED)
            ->orderBy('member_number')
            ->get(['id', 'member_number', 'name'])
            ->map(fn (Member $m) => [
                'id' => $m->id,
                'label' => '#'.$m->member_number.' '.$m->name,
            ])
            ->all();

        return Inertia::render('Accounting/Members/Groups/Index', [
            'groups' => $groups,
            'eligibleMembers' => $eligibleMembers,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Member::class);
        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('member_groups', 'code')->where(
                    fn ($q) => $q->where('company_id', $company->id),
                ),
            ],
            'name' => ['required', 'string', 'max:180'],
            'meeting_day' => ['nullable', 'string', 'max:16'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'member_ids' => ['array'],
            'member_ids.*' => [
                'integer',
                Rule::exists('members', 'id')->where(
                    fn ($q) => $q->where('company_id', $company->id)->where('status', Member::STATUS_APPROVED),
                ),
            ],
        ]);

        $group = MemberGroup::query()->create([
            'company_id' => $company->id,
            'code' => $validated['code'] ?? null,
            'name' => $validated['name'],
            'meeting_day' => $validated['meeting_day'] ?? null,
            'status' => MemberGroup::STATUS_ACTIVE,
            'created_by_user_id' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach (array_values(array_unique($validated['member_ids'] ?? [])) as $memberId) {
            MemberGroupMember::query()->create([
                'member_group_id' => $group->id,
                'member_id' => (int) $memberId,
                'joined_at' => now()->toDateString(),
            ]);
        }

        return redirect()
            ->route('member-groups.show', $this->withCompany($request, ['group' => $group->id]))
            ->with('status', __('Group created.'));
    }

    public function show(Request $request, int $group): Response
    {
        $this->authorize('viewAny', Member::class);
        $company = $this->accountingCompany($request);

        $record = MemberGroup::query()
            ->where('company_id', $company->id)
            ->with([
                'members.member:id,member_number,name,status',
            ])
            ->findOrFail($group);

        $memberIds = $record->members->whereNull('left_at')->pluck('member_id')->map(fn ($v) => (int) $v)->all();

        $positions = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', FinancialPosition::CATEGORY_SAVINGS)
            ->whereIn('member_id', $memberIds)
            ->with('member:id,member_number,name,status')
            ->orderBy('member_id')
            ->orderBy('id')
            ->get();

        $latestSavingsByMember = [];
        foreach ($positions as $pos) {
            if (! $pos->member_id || isset($latestSavingsByMember[$pos->member_id])) {
                continue;
            }
            if (! $pos->isSavingsOperational() || ! $pos->memberApprovedForFinance()) {
                continue;
            }
            $latestSavingsByMember[$pos->member_id] = $pos;
        }

        $loanPositions = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('category', FinancialPosition::CATEGORY_LOAN)
            ->whereIn('member_id', $memberIds)
            ->orderBy('member_id')
            ->orderBy('id')
            ->get();

        $latestLoanByMember = [];
        foreach ($loanPositions as $pos) {
            if (! $pos->member_id || isset($latestLoanByMember[$pos->member_id])) {
                continue;
            }
            if (! $pos->usesStructuredLoanFlow()
                || ! $pos->isStructuredCatalogOperational()
                || ! $pos->memberApprovedForFinance()) {
                continue;
            }
            $latestLoanByMember[$pos->member_id] = $pos;
        }

        $members = $record->members
            ->whereNull('left_at')
            ->map(function (MemberGroupMember $gm) use ($latestSavingsByMember, $latestLoanByMember) {
                $m = $gm->member;
                $posS = $m ? ($latestSavingsByMember[$m->id] ?? null) : null;
                $posL = $m ? ($latestLoanByMember[$m->id] ?? null) : null;

                return [
                    'member_id' => $m?->id,
                    'member_number' => $m?->member_number,
                    'member_name' => $m?->name,
                    'savings_position_id' => $posS?->id,
                    'savings_account_number' => $posS?->account_number,
                    'savings_principal_cents' => $posS ? (int) $posS->principal_cents : null,
                    'can_deposit' => $posS !== null,
                    'loan_position_id' => $posL?->id,
                    'loan_account_number' => $posL?->account_number,
                    'loan_principal_cents' => $posL ? (int) $posL->principal_cents : null,
                    'can_collect_loan' => $posL !== null,
                ];
            })
            ->values()
            ->all();

        $debitAccounts = ChartAccount::query()
            ->where('company_id', $company->id)
            ->approvedForJournals()
            ->where('type', ChartAccount::TYPE_ASSET)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (ChartAccount $a) => [
                'id' => $a->id,
                'label' => $a->code.' — '.$a->name,
            ])
            ->all();

        $interestRevenueAccounts = ChartAccount::query()
            ->where('company_id', $company->id)
            ->approvedForJournals()
            ->where('type', ChartAccount::TYPE_REVENUE)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (ChartAccount $a) => [
                'id' => $a->id,
                'label' => $a->code.' — '.$a->name,
            ])
            ->all();

        $batches = GroupDepositBatch::query()
            ->where('company_id', $company->id)
            ->where('member_group_id', $record->id)
            ->with(['debitChartAccount:id,code,name', 'user:id,name'])
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (GroupDepositBatch $b) => [
                'id' => $b->id,
                'transaction_date' => $b->transaction_date?->toDateString(),
                'reference' => $b->reference,
                'memo' => $b->memo,
                'total_cents' => (int) $b->total_cents,
                'line_count' => (int) $b->line_count,
                'debit_account' => $b->debitChartAccount ? ($b->debitChartAccount->code.' '.$b->debitChartAccount->name) : null,
                'user_name' => $b->user?->name,
            ]);

        $loanBatches = GroupLoanCollectionBatch::query()
            ->where('company_id', $company->id)
            ->where('member_group_id', $record->id)
            ->with([
                'debitChartAccount:id,code,name',
                'interestRevenueChartAccount:id,code,name',
                'penaltyCreditChartAccount:id,code,name',
                'user:id,name',
            ])
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (GroupLoanCollectionBatch $b) => [
                'id' => $b->id,
                'transaction_date' => $b->transaction_date?->toDateString(),
                'reference' => $b->reference,
                'memo' => $b->memo,
                'total_principal_cents' => (int) $b->total_principal_cents,
                'total_interest_cents' => (int) $b->total_interest_cents,
                'total_penalty_cents' => (int) $b->total_penalty_cents,
                'line_count' => (int) $b->line_count,
                'cash_account' => $b->debitChartAccount ? ($b->debitChartAccount->code.' '.$b->debitChartAccount->name) : null,
                'user_name' => $b->user?->name,
            ]);

        return Inertia::render('Accounting/Members/Groups/Show', [
            'group' => [
                'id' => $record->id,
                'code' => $record->code,
                'name' => $record->name,
                'meeting_day' => $record->meeting_day,
                'status' => $record->status,
                'notes' => $record->notes,
            ],
            'members' => $members,
            'debitAccounts' => $debitAccounts,
            'interestRevenueAccounts' => $interestRevenueAccounts,
            'batches' => $batches,
            'loanBatches' => $loanBatches,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function postDepositBatch(Request $request, int $group, FinanceJournalPostingService $posting): RedirectResponse
    {
        $this->authorize('viewAny', Member::class);
        $company = $this->accountingCompany($request);

        $record = MemberGroup::query()->where('company_id', $company->id)->findOrFail($group);

        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'debit_chart_account_id' => [
                'required',
                'integer',
                Rule::exists('chart_accounts', 'id')->where(
                    fn ($q) => $q->where('company_id', $company->id)->where('approval_status', ChartAccount::STATUS_APPROVED),
                ),
            ],
            'reference' => ['nullable', 'string', 'max:64'],
            'memo' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.member_id' => ['required', 'integer'],
            'lines.*.financial_position_id' => ['required', 'integer'],
            'lines.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $activeMemberIds = MemberGroupMember::query()
            ->where('member_group_id', $record->id)
            ->whereNull('left_at')
            ->pluck('member_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($activeMemberIds === []) {
            return back()->withErrors(['lines' => __('This group has no active members.')]);
        }

        $totalCents = 0;
        $postedCount = 0;
        $batch = null;

        try {
            DB::transaction(function () use (
                &$batch,
                &$totalCents,
                &$postedCount,
                $validated,
                $record,
                $company,
                $activeMemberIds,
                $posting,
                $request,
            ) {
                $batch = GroupDepositBatch::query()->create([
                    'company_id' => $company->id,
                    'member_group_id' => $record->id,
                    'user_id' => $request->user()->id,
                    'transaction_date' => $validated['transaction_date'],
                    'debit_chart_account_id' => (int) $validated['debit_chart_account_id'],
                    'reference' => $validated['reference'] ?? null,
                    'memo' => $validated['memo'] ?? null,
                    'total_cents' => 0,
                    'line_count' => 0,
                ]);

                foreach ($validated['lines'] as $line) {
                    $memberId = (int) $line['member_id'];
                    $positionId = (int) $line['financial_position_id'];
                    $amountCents = (int) round(((float) $line['amount']) * 100);

                    if ($amountCents <= 0) {
                        continue;
                    }

                    if (! in_array($memberId, $activeMemberIds, true)) {
                        throw new InvalidArgumentException(__('Member does not belong to this active group.'));
                    }

                    $position = FinancialPosition::query()
                        ->where('company_id', $company->id)
                        ->where('member_id', $memberId)
                        ->where('category', FinancialPosition::CATEGORY_SAVINGS)
                        ->findOrFail($positionId);

                    if (! $position->isSavingsOperational() || ! $position->memberApprovedForFinance()) {
                        throw new InvalidArgumentException(__('Member savings account is not operational.'));
                    }

                    $creditAccountId = $this->ensureMemberPersonalChartAccount($position, $request->user()->id);
                    if ($creditAccountId === (int) $validated['debit_chart_account_id']) {
                        throw new InvalidArgumentException(__('Debit account must differ from member savings ledger account.'));
                    }

                    $memo = __('Group savings deposit :member', [
                        'member' => '#'.$position->member?->member_number.' '.$position->member?->name,
                    ]);
                    if (! empty($validated['memo'])) {
                        $memo .= ' — '.$validated['memo'];
                    }

                    $entry = $posting->postTwoLineJournal(
                        $company->id,
                        $request->user(),
                        $validated['transaction_date'],
                        $memo,
                        $validated['reference'] ?? null,
                        $amountCents,
                        (int) $validated['debit_chart_account_id'],
                        $creditAccountId,
                        $memberId,
                        FinancialPosition::CATEGORY_SAVINGS,
                    );

                    $position->increment('principal_cents', $amountCents);
                    $position->refresh();

                    FinancialPositionMovement::query()->create([
                        'financial_position_id' => $position->id,
                        'company_id' => $company->id,
                        'user_id' => $request->user()->id,
                        'type' => FinancialPositionMovement::TYPE_DEPOSIT,
                        'amount_cents' => $amountCents,
                        'balance_after_cents' => (int) $position->principal_cents,
                        'memo' => __('Group deposit batch #:id', ['id' => $batch->id]),
                        'journal_entry_id' => $entry->id,
                    ]);

                    GroupDepositBatchLine::query()->create([
                        'group_deposit_batch_id' => $batch->id,
                        'member_id' => $memberId,
                        'financial_position_id' => $position->id,
                        'amount_cents' => $amountCents,
                        'journal_entry_id' => $entry->id,
                    ]);

                    $totalCents += $amountCents;
                    $postedCount++;
                }

                if ($postedCount === 0) {
                    throw new InvalidArgumentException(__('No valid deposit lines to post.'));
                }

                $batch->update([
                    'total_cents' => $totalCents,
                    'line_count' => $postedCount,
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['lines' => $e->getMessage()]);
        }

        app(AccountingAuditService::class)->logJournalAction(
            companyId: $company->id,
            journalEntryId: null,
            action: 'group_deposit.posted',
            actor: $request->user(),
            metadata: [
                'member_group_id' => $record->id,
                'batch_id' => $batch?->id,
                'line_count' => $postedCount,
                'total_cents' => $totalCents,
            ],
            request: $request,
        );

        return back()->with('status', __('Group deposit batch posted: :n lines.', ['n' => $postedCount]));
    }

    public function postLoanCollectionBatch(Request $request, int $group, FinanceJournalPostingService $posting): RedirectResponse
    {
        $this->authorize('viewAny', Member::class);
        $company = $this->accountingCompany($request);

        $record = MemberGroup::query()->where('company_id', $company->id)->findOrFail($group);

        if ($request->input('interest_revenue_chart_account_id') === '') {
            $request->merge(['interest_revenue_chart_account_id' => null]);
        }
        if ($request->input('penalty_credit_chart_account_id') === '') {
            $request->merge(['penalty_credit_chart_account_id' => null]);
        }

        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'debit_chart_account_id' => [
                'required',
                'integer',
                Rule::exists('chart_accounts', 'id')->where(
                    fn ($q) => $q->where('company_id', $company->id)->where('approval_status', ChartAccount::STATUS_APPROVED),
                ),
            ],
            'interest_revenue_chart_account_id' => [
                'nullable',
                'integer',
                Rule::exists('chart_accounts', 'id')->where(
                    fn ($q) => $q->where('company_id', $company->id)->where('approval_status', ChartAccount::STATUS_APPROVED),
                ),
            ],
            'penalty_credit_chart_account_id' => [
                'nullable',
                'integer',
                Rule::exists('chart_accounts', 'id')->where(
                    fn ($q) => $q->where('company_id', $company->id)->where('approval_status', ChartAccount::STATUS_APPROVED),
                ),
            ],
            'reference' => ['nullable', 'string', 'max:64'],
            'memo' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.member_id' => ['required', 'integer'],
            'lines.*.financial_position_id' => ['required', 'integer'],
            'lines.*.principal' => ['nullable', 'numeric', 'min:0'],
            'lines.*.interest' => ['nullable', 'numeric', 'min:0'],
            'lines.*.penalty' => ['nullable', 'numeric', 'min:0'],
        ]);

        $activeMemberIds = MemberGroupMember::query()
            ->where('member_group_id', $record->id)
            ->whereNull('left_at')
            ->pluck('member_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($activeMemberIds === []) {
            return back()->withErrors(['loan_lines' => __('This group has no active members.')]);
        }

        $cashId = (int) $validated['debit_chart_account_id'];
        $interestRevId = isset($validated['interest_revenue_chart_account_id'])
            ? (int) $validated['interest_revenue_chart_account_id']
            : 0;
        $penaltyCreditId = isset($validated['penalty_credit_chart_account_id'])
            ? (int) $validated['penalty_credit_chart_account_id']
            : 0;

        $needsInterest = false;
        $needsPenaltyCredit = false;
        foreach ($validated['lines'] as $line) {
            $ic = (int) round(((float) ($line['interest'] ?? 0)) * 100);
            $pc = (int) round(((float) ($line['penalty'] ?? 0)) * 100);
            if ($ic > 0) {
                $needsInterest = true;
            }
            if ($pc > 0) {
                $needsPenaltyCredit = true;
            }
        }

        if ($needsInterest && $interestRevId <= 0) {
            return back()->withErrors([
                'interest_revenue_chart_account_id' => __('Select an interest income account when any line has interest.'),
            ]);
        }

        if ($needsPenaltyCredit && $penaltyCreditId <= 0) {
            return back()->withErrors([
                'penalty_credit_chart_account_id' => __('Select the credit account for penalties (usually cash / bank) when any line has a penalty.'),
            ]);
        }

        if ($interestRevId > 0 && $interestRevId === $cashId) {
            return back()->withErrors([
                'interest_revenue_chart_account_id' => __('Interest income account must differ from the cash / bank account.'),
            ]);
        }

        $totalPrincipal = 0;
        $totalInterest = 0;
        $totalPenalty = 0;
        $postedCount = 0;
        $batch = null;

        try {
            DB::transaction(function () use (
                &$batch,
                &$totalPrincipal,
                &$totalInterest,
                &$totalPenalty,
                &$postedCount,
                $validated,
                $record,
                $company,
                $activeMemberIds,
                $posting,
                $request,
                $cashId,
                $interestRevId,
                $penaltyCreditId,
                $needsInterest,
                $needsPenaltyCredit,
            ) {
                $batch = GroupLoanCollectionBatch::query()->create([
                    'company_id' => $company->id,
                    'member_group_id' => $record->id,
                    'user_id' => $request->user()->id,
                    'transaction_date' => $validated['transaction_date'],
                    'debit_chart_account_id' => $cashId,
                    'interest_revenue_chart_account_id' => $needsInterest ? $interestRevId : null,
                    'penalty_credit_chart_account_id' => $needsPenaltyCredit ? $penaltyCreditId : null,
                    'reference' => $validated['reference'] ?? null,
                    'memo' => $validated['memo'] ?? null,
                    'total_principal_cents' => 0,
                    'total_interest_cents' => 0,
                    'total_penalty_cents' => 0,
                    'line_count' => 0,
                ]);

                foreach ($validated['lines'] as $line) {
                    $memberId = (int) $line['member_id'];
                    $positionId = (int) $line['financial_position_id'];
                    $principalCents = (int) round(((float) ($line['principal'] ?? 0)) * 100);
                    $interestCents = (int) round(((float) ($line['interest'] ?? 0)) * 100);
                    $penaltyCents = (int) round(((float) ($line['penalty'] ?? 0)) * 100);

                    if ($principalCents <= 0 && $interestCents <= 0 && $penaltyCents <= 0) {
                        continue;
                    }

                    if (! in_array($memberId, $activeMemberIds, true)) {
                        throw new InvalidArgumentException(__('Member does not belong to this active group.'));
                    }

                    $position = FinancialPosition::query()
                        ->where('company_id', $company->id)
                        ->where('member_id', $memberId)
                        ->where('category', FinancialPosition::CATEGORY_LOAN)
                        ->findOrFail($positionId);

                    Gate::forUser($request->user())->authorize('update', $position);

                    if (! $position->usesStructuredLoanFlow()) {
                        throw new InvalidArgumentException(__('Group collection applies to product-based loans only.'));
                    }

                    if (! $position->isStructuredCatalogOperational() || ! $position->memberApprovedForFinance()) {
                        throw new InvalidArgumentException(__('Loan account is not operational for this member.'));
                    }

                    $position->loadMissing('member');

                    $memberPersonalId = $this->ensureLoanMemberPersonalChartAccount($position, $request->user());

                    $lineRow = [
                        'principal_journal_entry_id' => null,
                        'interest_journal_entry_id' => null,
                        'penalty_journal_entry_id' => null,
                    ];

                    if ($penaltyCents > 0) {
                        if ($penaltyCreditId === $memberPersonalId) {
                            throw new InvalidArgumentException(__('Penalty credit account must differ from the member loan ledger account.'));
                        }

                        $memo = __('Loan penalty / late charge :title', ['title' => $position->title])
                            .$this->financeMemberMemoSuffix($position);
                        if (! empty($validated['memo'])) {
                            $memo .= ' — '.$validated['memo'];
                        }

                        $penEntry = $posting->postTwoLineJournal(
                            $company->id,
                            $request->user(),
                            $validated['transaction_date'],
                            $memo,
                            $validated['reference'] ?? null,
                            $penaltyCents,
                            $memberPersonalId,
                            $penaltyCreditId,
                            $memberId,
                            FinancialPosition::CATEGORY_LOAN,
                        );
                        $lineRow['penalty_journal_entry_id'] = $penEntry->id;

                        $position->increment('principal_cents', $penaltyCents);
                        $position->refresh();

                        FinancialPositionMovement::query()->create([
                            'financial_position_id' => $position->id,
                            'company_id' => $company->id,
                            'user_id' => $request->user()->id,
                            'type' => FinancialPositionMovement::TYPE_PENALTY,
                            'amount_cents' => $penaltyCents,
                            'balance_after_cents' => (int) $position->principal_cents,
                            'memo' => __('Group loan collection batch #:id', ['id' => $batch->id]),
                            'journal_entry_id' => $penEntry->id,
                        ]);

                        $totalPenalty += $penaltyCents;
                    }

                    if ($principalCents > 0) {
                        if ($cashId === $memberPersonalId) {
                            throw new InvalidArgumentException(__('Cash / bank account must differ from the member loan ledger account.'));
                        }

                        if ((int) $position->principal_cents < $principalCents) {
                            throw new InvalidArgumentException(
                                __('Installment exceeds outstanding principal for :acct.', [
                                    'acct' => (string) $position->account_number,
                                ]),
                            );
                        }

                        $memo = __('Loan installment / principal repayment :title', ['title' => $position->title])
                            .$this->financeMemberMemoSuffix($position);
                        if (! empty($validated['memo'])) {
                            $memo .= ' — '.$validated['memo'];
                        }

                        $prEntry = $posting->postTwoLineJournal(
                            $company->id,
                            $request->user(),
                            $validated['transaction_date'],
                            $memo,
                            $validated['reference'] ?? null,
                            $principalCents,
                            $cashId,
                            $memberPersonalId,
                            $memberId,
                            FinancialPosition::CATEGORY_LOAN,
                        );
                        $lineRow['principal_journal_entry_id'] = $prEntry->id;

                        $position->decrement('principal_cents', $principalCents);
                        $position->refresh();

                        FinancialPositionMovement::query()->create([
                            'financial_position_id' => $position->id,
                            'company_id' => $company->id,
                            'user_id' => $request->user()->id,
                            'type' => FinancialPositionMovement::TYPE_INSTALLMENT,
                            'amount_cents' => -$principalCents,
                            'balance_after_cents' => (int) $position->principal_cents,
                            'memo' => __('Group loan collection batch #:id', ['id' => $batch->id]),
                            'journal_entry_id' => $prEntry->id,
                        ]);

                        $totalPrincipal += $principalCents;
                    }

                    if ($interestCents > 0) {
                        if ($cashId === $interestRevId) {
                            throw new InvalidArgumentException(__('Cash / bank must differ from interest income account.'));
                        }

                        $memo = __('Loan interest receipt :title', ['title' => $position->title])
                            .$this->financeMemberMemoSuffix($position);
                        if (! empty($validated['memo'])) {
                            $memo .= ' — '.$validated['memo'];
                        }

                        $intEntry = $posting->postTwoLineJournal(
                            $company->id,
                            $request->user(),
                            $validated['transaction_date'],
                            $memo,
                            $validated['reference'] ?? null,
                            $interestCents,
                            $cashId,
                            $interestRevId,
                            $memberId,
                            FinancialPosition::CATEGORY_LOAN,
                        );
                        $lineRow['interest_journal_entry_id'] = $intEntry->id;

                        $balanceAfter = (int) $position->principal_cents;

                        FinancialPositionMovement::query()->create([
                            'financial_position_id' => $position->id,
                            'company_id' => $company->id,
                            'user_id' => $request->user()->id,
                            'type' => FinancialPositionMovement::TYPE_INTEREST_RECEIPT,
                            'amount_cents' => $interestCents,
                            'balance_after_cents' => $balanceAfter,
                            'memo' => __('Group loan collection batch #:id', ['id' => $batch->id]),
                            'journal_entry_id' => $intEntry->id,
                        ]);

                        $totalInterest += $interestCents;
                    }

                    GroupLoanCollectionBatchLine::query()->create([
                        'group_loan_collection_batch_id' => $batch->id,
                        'member_id' => $memberId,
                        'financial_position_id' => $position->id,
                        'principal_cents' => $principalCents,
                        'interest_cents' => $interestCents,
                        'penalty_cents' => $penaltyCents,
                        'principal_journal_entry_id' => $lineRow['principal_journal_entry_id'],
                        'interest_journal_entry_id' => $lineRow['interest_journal_entry_id'],
                        'penalty_journal_entry_id' => $lineRow['penalty_journal_entry_id'],
                    ]);

                    $postedCount++;
                }

                if ($postedCount === 0) {
                    throw new InvalidArgumentException(__('No valid collection lines to post.'));
                }

                $batch->update([
                    'total_principal_cents' => $totalPrincipal,
                    'total_interest_cents' => $totalInterest,
                    'total_penalty_cents' => $totalPenalty,
                    'line_count' => $postedCount,
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['loan_lines' => $e->getMessage()]);
        }

        app(AccountingAuditService::class)->logJournalAction(
            companyId: $company->id,
            journalEntryId: null,
            action: 'group_loan_collection.posted',
            actor: $request->user(),
            metadata: [
                'member_group_id' => $record->id,
                'batch_id' => $batch?->id,
                'line_count' => $postedCount,
                'total_principal_cents' => $totalPrincipal,
                'total_interest_cents' => $totalInterest,
                'total_penalty_cents' => $totalPenalty,
            ],
            request: $request,
        );

        return back()->with('status', __('Group loan collection batch posted: :n lines.', ['n' => $postedCount]));
    }

    private function ensureMemberPersonalChartAccount(FinancialPosition $record, int $actorUserId): int
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
            if (! $existing->isApproved()) {
                $existing->update([
                    'approval_status' => ChartAccount::STATUS_APPROVED,
                    'approved_at' => now(),
                    'approved_by_user_id' => $actorUserId,
                    'approved_by_admin_id' => null,
                ]);
            }

            return (int) $existing->id;
        }

        $memberNo = $record->member?->member_number ? '#'.$record->member->member_number : __('member');
        $memberName = trim((string) ($record->member?->name ?? $record->title));

        $created = ChartAccount::query()->create([
            'company_id' => $record->company_id,
            'user_id' => $actorUserId,
            'code' => $accountCode,
            'name' => __('Savings account :no :name', ['no' => $memberNo, 'name' => $memberName]),
            'type' => ChartAccount::TYPE_LIABILITY,
            'description' => __('Auto-created member savings ledger account.'),
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => $actorUserId,
            'approved_by_admin_id' => null,
        ]);

        return (int) $created->id;
    }

    private function ensureLoanMemberPersonalChartAccount(FinancialPosition $record, User $actor): int
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

        $created = ChartAccount::query()->create([
            'company_id' => $record->company_id,
            'user_id' => $actor->id,
            'code' => $accountCode,
            'name' => 'Loan personal '.$memberName,
            'type' => ChartAccount::TYPE_ASSET,
            'description' => __('Auto-created member personal account for finance posting.'),
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => $actor->id,
            'approved_by_admin_id' => null,
        ]);

        return (int) $created->id;
    }

    private function financeMemberMemoSuffix(FinancialPosition $record): string
    {
        if ($record->member === null) {
            return '';
        }

        return ' — '.__('Member').' #'.$record->member->member_number.': '.$record->member->name;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function withCompany(Request $request, array $params): array
    {
        if (! $request->user()->isAdmin()) {
            return $params;
        }

        $params['company_id'] = $this->accountingCompany($request)->id;

        return $params;
    }
}
