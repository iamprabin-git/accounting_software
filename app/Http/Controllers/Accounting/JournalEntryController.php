<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Controller;
use App\Models\AccountingAuditLog;
use App\Models\ChartAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\TellerDayClose;
use App\Services\AccountingAuditService;
use App\Support\MoneyAmount;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends Controller
{
    use ResolvesAccountingCompany;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);

        $entries = JournalEntry::query()
            ->forCompany($company->id)
            ->with(['user:id,name', 'approvedBy:id,name', 'firstApprovedBy:id,name'])
            ->withCount('lines')
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (JournalEntry $e) => [
                'id' => $e->id,
                'transaction_date' => $e->transaction_date->toDateString(),
                'reference' => $e->reference,
                'memo' => $e->memo,
                'status' => $e->status,
                'posted_number' => $e->posted_number,
                'lines_count' => $e->lines_count,
                'creator_name' => $e->user?->name,
                'approved_by_name' => $e->approvedBy?->name,
                'approved_at' => $e->approved_at?->toIso8601String(),
                'submitted_at' => $e->submitted_at?->toIso8601String(),
                'pending_age_days' => $e->submitted_at
                    ? (int) $e->submitted_at->copy()->startOfDay()->diffInDays(now()->startOfDay())
                    : null,
                'first_approved_by_name' => $e->firstApprovedBy?->name,
            ]);

        return Inertia::render('Accounting/Journals/Index', [
            'journalEntries' => $entries,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'letterhead' => $this->companyLetterhead($company),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', JournalEntry::class);

        $company = $this->accountingCompany($request);

        return Inertia::render('Accounting/Journals/Create', [
            'accounts' => $this->chartAccountOptions($company->id),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function createCashIn(Request $request): Response|RedirectResponse
    {
        return $this->createCashEntryForm($request, 'in');
    }

    public function createCashOut(Request $request): Response|RedirectResponse
    {
        return $this->createCashEntryForm($request, 'out');
    }

    public function storeCashIn(Request $request): RedirectResponse
    {
        return $this->storeCashMovement($request, 'in');
    }

    public function storeCashOut(Request $request): RedirectResponse
    {
        return $this->storeCashMovement($request, 'out');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', JournalEntry::class);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $validated = $this->validatedEntryPayload($request, $company->id);

        DB::transaction(function () use ($request, $company, $validated) {
            $entry = JournalEntry::query()->create([
                'company_id' => $company->id,
                'user_id' => $request->user()->id,
                'reference' => $validated['reference'],
                'memo' => $validated['memo'],
                'transaction_date' => $validated['transaction_date'],
                'status' => JournalEntry::STATUS_DRAFT,
            ]);

            foreach ($validated['lines'] as $line) {
                $entry->lines()->create([
                    'chart_account_id' => $line['chart_account_id'],
                    'debit_cents' => $line['debit_cents'],
                    'credit_cents' => $line['credit_cents'],
                    'description' => $line['description'],
                ]);
            }

            app(AccountingAuditService::class)->logForJournal(
                $entry,
                'journal.created_draft',
                $request->user(),
                [
                    'after' => [
                        'status' => $entry->status,
                        'transaction_date' => $entry->transaction_date?->toDateString(),
                        'lines_count' => $entry->lines()->count(),
                    ],
                ],
                $request,
            );
        });

        return redirect()->route('journals.index', $this->companyQuery($request))
            ->with('status', __('Journal entry saved as draft.'));
    }

    public function show(Request $request, int $journal): Response
    {
        $company = $this->accountingCompany($request);

        $journalEntry = JournalEntry::query()
            ->forCompany($company->id)
            ->with([
                'lines.chartAccount',
                'user:id,name',
                'approvedBy:id,name',
                'firstApprovedBy:id,name',
                'reversalOf:id',
                'reversalEntry:id,reversal_of_journal_entry_id',
                'approvalComments.user:id,name',
            ])
            ->findOrFail($journal);

        $this->authorize('view', $journalEntry);

        return Inertia::render('Accounting/Journals/Show', [
            'journal' => $this->journalPayload($journalEntry),
            'can_update' => $request->user()->can('update', $journalEntry),
            'can_submit' => $request->user()->can('submit', $journalEntry),
            'can_approve' => $request->user()->can('approve', $journalEntry),
            'can_reject' => $request->user()->can('reject', $journalEntry),
            'can_delete' => $request->user()->can('delete', $journalEntry),
            'can_reverse' => $request->user()->can('create', JournalEntry::class),
            'printMode' => $request->boolean('print'),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'letterhead' => $this->companyLetterhead($company),
            'audit_logs' => AccountingAuditLog::query()
                ->where('company_id', $company->id)
                ->where('journal_entry_id', $journalEntry->id)
                ->with('user:id,name')
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(fn (AccountingAuditLog $log) => [
                    'action' => $log->action,
                    'actor_name' => $log->user?->name ?? 'System',
                    'metadata' => $log->metadata ?? [],
                    'created_at' => $log->created_at?->toIso8601String(),
                ])
                ->all(),
            'approval_comments' => $journalEntry->approvalComments
                ->sortByDesc('id')
                ->take(20)
                ->map(fn ($c) => [
                    'action' => $c->action,
                    'comment' => $c->comment,
                    'actor_name' => $c->user?->name ?? 'System',
                    'created_at' => $c->created_at?->toIso8601String(),
                ])->values()->all(),
        ]);
    }

    public function edit(Request $request, int $journal): Response
    {
        $company = $this->accountingCompany($request);

        $journalEntry = JournalEntry::query()
            ->forCompany($company->id)
            ->with(['lines.chartAccount'])
            ->findOrFail($journal);

        $this->authorize('update', $journalEntry);

        if ($journalEntry->isApproved()) {
            return redirect()->route('journals.show', array_merge(
                ['journal' => $journalEntry->id],
                $this->companyQuery($request),
            ))->withErrors([
                'update' => __('Approved journals are immutable. Post a reversing journal, then re-post with corrections.'),
            ]);
        }

        return Inertia::render('Accounting/Journals/Edit', [
            'journal' => $this->journalPayload($journalEntry),
            'accounts' => $this->chartAccountOptions($company->id),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function update(Request $request, int $journal): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $journalEntry = JournalEntry::query()
            ->forCompany($company->id)
            ->findOrFail($journal);

        $this->authorize('update', $journalEntry);

        $validated = $this->validatedEntryPayload($request, $company->id);

        DB::transaction(function () use ($journalEntry, $validated, $request) {
            $before = [
                'reference' => $journalEntry->reference,
                'memo' => $journalEntry->memo,
                'transaction_date' => $journalEntry->transaction_date?->toDateString(),
            ];
            $journalEntry->update([
                'reference' => $validated['reference'],
                'memo' => $validated['memo'],
                'transaction_date' => $validated['transaction_date'],
            ]);

            $journalEntry->lines()->delete();

            foreach ($validated['lines'] as $line) {
                $journalEntry->lines()->create([
                    'chart_account_id' => $line['chart_account_id'],
                    'debit_cents' => $line['debit_cents'],
                    'credit_cents' => $line['credit_cents'],
                    'description' => $line['description'],
                ]);
            }

            app(AccountingAuditService::class)->logForJournal(
                $journalEntry,
                'journal.updated',
                $request->user(),
                [
                    'before' => $before,
                    'after' => [
                        'reference' => $journalEntry->reference,
                        'memo' => $journalEntry->memo,
                        'transaction_date' => $journalEntry->transaction_date?->toDateString(),
                    ],
                ],
                $request,
            );
        });

        return redirect()->route('journals.show', array_merge(
            ['journal' => $journalEntry->id],
            $this->companyQuery($request),
        ))->with('status', __('Journal entry updated.'));
    }

    public function destroy(Request $request, int $journal): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $journalEntry = JournalEntry::query()
            ->forCompany($company->id)
            ->findOrFail($journal);

        $this->authorize('delete', $journalEntry);

        if ($journalEntry->isApproved()) {
            return redirect()->route('journals.show', array_merge(
                ['journal' => $journalEntry->id],
                $this->companyQuery($request),
            ))->withErrors([
                'delete' => __('Approved journals are immutable. Use a reversing journal instead of deleting.'),
            ]);
        }

        app(AccountingAuditService::class)->logForJournal(
            $journalEntry,
            'journal.deleted',
            $request->user(),
            [
                'before' => [
                    'status' => $journalEntry->status,
                    'transaction_date' => $journalEntry->transaction_date?->toDateString(),
                ],
            ],
            $request,
        );

        $journalEntry->delete();

        return redirect()->route('journals.index', $this->companyQuery($request))
            ->with('status', __('Journal entry deleted.'));
    }

    public function submit(Request $request, int $journal): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $journalEntry = JournalEntry::query()
            ->forCompany($company->id)
            ->with('lines')
            ->findOrFail($journal);

        $this->authorize('submit', $journalEntry);

        if (! $journalEntry->fresh(['lines'])->isBalanced()) {
            return redirect()->route('journals.show', array_merge(
                ['journal' => $journalEntry->id],
                $this->companyQuery($request),
            ))->withErrors([
                'submit' => __('This entry is not balanced and cannot be submitted for approval.'),
            ]);
        }

        $journalEntry->update([
            'status' => JournalEntry::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        app(AccountingAuditService::class)->logForJournal(
            $journalEntry,
            'journal.submitted',
            $request->user(),
            [
                'after' => [
                    'status' => $journalEntry->status,
                    'submitted_at' => $journalEntry->submitted_at?->toIso8601String(),
                ],
            ],
            $request,
        );

        return redirect()->route('journals.show', array_merge(
            ['journal' => $journalEntry->id],
            $this->companyQuery($request),
        ))->with('status', __('Submitted for approval.'));
    }

    public function reverse(Request $request, int $journal): RedirectResponse
    {
        $this->authorize('create', JournalEntry::class);
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $journalEntry = JournalEntry::query()
            ->forCompany($company->id)
            ->with(['lines', 'reversalEntry'])
            ->findOrFail($journal);

        $this->authorize('view', $journalEntry);

        if (! $journalEntry->isApproved()) {
            return redirect()->route('journals.show', array_merge(
                ['journal' => $journalEntry->id],
                $this->companyQuery($request),
            ))->withErrors([
                'reverse' => __('Only approved journals can be reversed.'),
            ]);
        }

        if ($journalEntry->reversalEntry !== null) {
            return redirect()->route('journals.show', array_merge(
                ['journal' => $journalEntry->id],
                $this->companyQuery($request),
            ))->withErrors([
                'reverse' => __('This journal already has a reversal entry.'),
            ]);
        }

        $newJournalId = null;

        DB::transaction(function () use ($request, $journalEntry, &$newJournalId) {
            $reversal = JournalEntry::query()->create([
                'company_id' => $journalEntry->company_id,
                'member_id' => $journalEntry->member_id,
                'finance_category' => $journalEntry->finance_category,
                'user_id' => $request->user()->id,
                'reference' => $journalEntry->reference
                    ? 'REV-'.$journalEntry->reference
                    : 'REV-J'.$journalEntry->id,
                'memo' => __('Reversal of journal #:id', ['id' => $journalEntry->id]),
                'transaction_date' => now()->toDateString(),
                'status' => JournalEntry::STATUS_DRAFT,
                'reversal_of_journal_entry_id' => $journalEntry->id,
            ]);

            foreach ($journalEntry->lines as $line) {
                $reversal->lines()->create([
                    'chart_account_id' => $line->chart_account_id,
                    'debit_cents' => (int) $line->credit_cents,
                    'credit_cents' => (int) $line->debit_cents,
                    'description' => $line->description,
                ]);
            }

            app(AccountingAuditService::class)->logForJournal(
                $journalEntry,
                'journal.reversal_created',
                $request->user(),
                ['reversal_journal_entry_id' => $reversal->id],
                $request,
            );
            app(AccountingAuditService::class)->logForJournal(
                $reversal,
                'journal.created_reversal_draft',
                $request->user(),
                ['source_journal_entry_id' => $journalEntry->id],
                $request,
            );

            $newJournalId = (int) $reversal->id;
        });

        return redirect()->route('journals.show', array_merge(
            ['journal' => $newJournalId],
            $this->companyQuery($request),
        ))->with('status', __('Reversal draft created. Review and submit for approval.'));
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
     * @return array<string, mixed>
     */
    private function journalPayload(JournalEntry $journalEntry): array
    {
        return [
            'id' => $journalEntry->id,
            'transaction_date' => $journalEntry->transaction_date->toDateString(),
            'reference' => $journalEntry->reference,
            'memo' => $journalEntry->memo,
            'status' => $journalEntry->status,
            'posted_number' => $journalEntry->posted_number,
            'reversal_of_journal_entry_id' => $journalEntry->reversal_of_journal_entry_id,
            'reversal_journal_entry_id' => $journalEntry->reversalEntry?->id,
            'creator_name' => $journalEntry->user?->name,
            'approved_by_name' => $journalEntry->approvedBy?->name,
            'first_approved_by_name' => $journalEntry->firstApprovedBy?->name,
            'first_approved_at' => $journalEntry->first_approved_at?->toIso8601String(),
            'approved_at' => $journalEntry->approved_at?->toIso8601String(),
            'submitted_at' => $journalEntry->submitted_at?->toIso8601String(),
            'pending_age_days' => $journalEntry->submitted_at
                ? (int) $journalEntry->submitted_at->copy()->startOfDay()->diffInDays(now()->startOfDay())
                : null,
            'lines' => $journalEntry->lines->map(fn (JournalLine $line) => [
                'id' => $line->id,
                'chart_account_id' => $line->chart_account_id,
                'account_code' => $line->chartAccount?->code,
                'account_name' => $line->chartAccount?->name,
                'debit_cents' => (int) $line->debit_cents,
                'credit_cents' => (int) $line->credit_cents,
                'debit' => round(((int) $line->debit_cents) / 100, 2),
                'credit' => round(((int) $line->credit_cents) / 100, 2),
                'description' => $line->description,
            ])->all(),
        ];
    }

    /**
     * @return array{reference: ?string, memo: ?string, transaction_date: string, lines: list<array{chart_account_id: int, debit_cents: int, credit_cents: int, description: ?string}>}
     */
    private function validatedEntryPayload(Request $request, int $companyId): array
    {
        $validated = $request->validate([
            'reference' => ['nullable', 'string', 'max:64'],
            'memo' => ['nullable', 'string', 'max:500'],
            'transaction_date' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.chart_account_id' => [
                'required',
                'integer',
                Rule::exists('chart_accounts', 'id')->where(
                    fn ($q) => $q->where('company_id', $companyId)
                        ->where('approval_status', ChartAccount::STATUS_APPROVED)
                ),
            ],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $linesOut = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($validated['lines'] as $i => $line) {
            $d = MoneyAmount::numericInputToCents($line['debit'] ?? 0);
            $c = MoneyAmount::numericInputToCents($line['credit'] ?? 0);

            if (($d > 0 && $c > 0) || ($d === 0 && $c === 0)) {
                throw ValidationException::withMessages([
                    "lines.$i.debit" => __('Each line must have either a debit or a credit amount (not both).'),
                ]);
            }

            $linesOut[] = [
                'chart_account_id' => (int) $line['chart_account_id'],
                'debit_cents' => $d,
                'credit_cents' => $c,
                'description' => $line['description'] ?? null,
            ];

            $totalDebit += $d;
            $totalCredit += $c;
        }

        if ($totalDebit !== $totalCredit || $totalDebit === 0) {
            throw ValidationException::withMessages([
                'lines' => __('Total debits must equal total credits and cannot be zero.'),
            ]);
        }

        return [
            'reference' => $validated['reference'] ?? null,
            'memo' => $validated['memo'] ?? null,
            'transaction_date' => $validated['transaction_date'],
            'lines' => $linesOut,
        ];
    }

    private function createCashEntryForm(Request $request, string $direction): Response|RedirectResponse
    {
        $this->authorize('create', JournalEntry::class);

        $company = $this->accountingCompany($request);
        $activeOpenDayDate = TellerDayClose::query()
            ->where('company_id', $company->id)
            ->where('day_status', TellerDayClose::STATUS_OPEN)
            ->latest('close_date')
            ->value('close_date');
        $businessDate = (string) ($request->input('date')
            ?: ($activeOpenDayDate ? Carbon::parse($activeOpenDayDate)->toDateString() : Carbon::today()->toDateString()));

        $openDay = TellerDayClose::query()
            ->where('company_id', $company->id)
            ->whereDate('close_date', $businessDate)
            ->where('day_status', TellerDayClose::STATUS_OPEN)
            ->exists();

        if (! $openDay) {
            return redirect()->route('teller.day-close.create', array_merge(
                $this->companyQuery($request),
                ['date' => $businessDate],
            ))->withErrors([
                'close_date' => __('Start teller day first for :date before opening cash receive/payment.', ['date' => $businessDate]),
            ]);
        }

        $defaultCashAccountId = $this->defaultCashAccountId($company->id);
        $defaultCashAccountLabel = null;

        if ($defaultCashAccountId !== null) {
            $defaultCashAccountLabel = ChartAccount::query()
                ->where('company_id', $company->id)
                ->whereKey($defaultCashAccountId)
                ->value(DB::raw("CONCAT(code, ' - ', name)"));
        }

        return Inertia::render('Accounting/Journals/CashEntryCreate', [
            'mode' => $direction,
            'accounts' => $this->chartAccountOptions($company->id),
            'defaultCashAccountId' => $defaultCashAccountId,
            'defaultCashAccountLabel' => $defaultCashAccountLabel,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    private function storeCashMovement(Request $request, string $direction): RedirectResponse
    {
        $this->authorize('create', JournalEntry::class);

        $this->validateAdminCompanySelection($request);

        if (! in_array($direction, ['in', 'out'], true)) {
            abort(404);
        }

        $company = $this->accountingCompany($request);

        $accountRule = Rule::exists('chart_accounts', 'id')->where(
            fn ($q) => $q->where('company_id', $company->id)
                ->where('approval_status', ChartAccount::STATUS_APPROVED)
        );

        $validated = $request->validate([
            'cash_chart_account_id' => ['required', 'integer', $accountRule],
            'reference' => ['nullable', 'string', 'max:64'],
            'memo' => ['nullable', 'string', 'max:500'],
            'transaction_date' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.chart_account_id' => ['required', 'integer', $accountRule],
            'lines.*.amount' => ['required', 'numeric', 'min:0.01'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        if (in_array($direction, ['in', 'out'], true)) {
            $defaultCashAccountId = $this->defaultCashAccountId($company->id);

            if ($defaultCashAccountId === null) {
                throw ValidationException::withMessages([
                    'cash_chart_account_id' => __('Set up an approved "Cash in Hand" account first.'),
                ]);
            }

            $validated['cash_chart_account_id'] = $defaultCashAccountId;
        }

        $cashId = (int) $validated['cash_chart_account_id'];
        $transactionDate = (string) $validated['transaction_date'];

        $openDay = TellerDayClose::query()
            ->where('company_id', $company->id)
            ->whereDate('close_date', $transactionDate)
            ->where('day_status', TellerDayClose::STATUS_OPEN)
            ->first();

        if (! $openDay) {
            throw ValidationException::withMessages([
                'transaction_date' => __('Start the teller day first. Cash transactions are allowed only for open days.'),
            ]);
        }

        $counterpartLines = [];
        $totalCents = 0;

        foreach ($validated['lines'] as $i => $line) {
            $accId = (int) $line['chart_account_id'];

            if ($accId === $cashId) {
                throw ValidationException::withMessages([
                    "lines.$i.chart_account_id" => __('Counterpart lines must not use the same account as cash in hand.'),
                ]);
            }

            $cents = MoneyAmount::numericInputToCents($line['amount']);

            if ($cents <= 0) {
                throw ValidationException::withMessages([
                    "lines.$i.amount" => __('Enter a positive amount.'),
                ]);
            }

            $totalCents += $cents;
            $counterpartLines[] = [
                'chart_account_id' => $accId,
                'cents' => $cents,
                'description' => $line['description'] ?? null,
            ];
        }

        $memo = trim((string) ($validated['memo'] ?? ''));
        if ($memo === '') {
            $memo = $direction === 'in'
                ? (string) __('Cash receipt')
                : (string) __('Cash payment');
        }

        $linesForDb = [];

        if ($direction === 'in') {
            $linesForDb[] = [
                'chart_account_id' => $cashId,
                'debit_cents' => $totalCents,
                'credit_cents' => 0,
                'description' => (string) __('Cash in'),
            ];
            foreach ($counterpartLines as $row) {
                $linesForDb[] = [
                    'chart_account_id' => $row['chart_account_id'],
                    'debit_cents' => 0,
                    'credit_cents' => $row['cents'],
                    'description' => $row['description'],
                ];
            }
        } else {
            foreach ($counterpartLines as $row) {
                $linesForDb[] = [
                    'chart_account_id' => $row['chart_account_id'],
                    'debit_cents' => $row['cents'],
                    'credit_cents' => 0,
                    'description' => $row['description'],
                ];
            }
            $linesForDb[] = [
                'chart_account_id' => $cashId,
                'debit_cents' => 0,
                'credit_cents' => $totalCents,
                'description' => (string) __('Cash out'),
            ];
        }

        DB::transaction(function () use ($request, $company, $validated, $memo, $linesForDb, $direction, $openDay, $totalCents) {
            $entry = JournalEntry::query()->create([
                'company_id' => $company->id,
                'user_id' => $request->user()->id,
                'reference' => $validated['reference'] ?? null,
                'memo' => $memo,
                'transaction_date' => $validated['transaction_date'],
                'status' => JournalEntry::STATUS_DRAFT,
            ]);

            foreach ($linesForDb as $line) {
                $entry->lines()->create($line);
            }

            app(AccountingAuditService::class)->logForJournal(
                $entry,
                $direction === 'in' ? 'journal.cash_in_created_draft' : 'journal.cash_out_created_draft',
                $request->user(),
                [
                    'after' => [
                        'status' => $entry->status,
                        'direction' => $direction,
                        'transaction_date' => $entry->transaction_date?->toDateString(),
                    ],
                ],
                $request,
            );

            TellerDayClose::query()
                ->whereKey($openDay->id)
                ->update([
                    'cash_received_cents' => DB::raw('cash_received_cents + '.(int) $totalCents),
                ]);
        });

        $statusMsg = $direction === 'in'
            ? __('Cash in entry saved as draft.')
            : __('Cash out entry saved as draft.');

        return redirect()->route('journals.index', $this->companyQuery($request))
            ->with('status', $statusMsg);
    }

    private function defaultCashAccountId(int $companyId): ?int
    {
        $base = ChartAccount::query()
            ->where('company_id', $companyId)
            ->where('approval_status', ChartAccount::STATUS_APPROVED)
            ->where('type', ChartAccount::TYPE_ASSET);

        return (clone $base)
            ->whereRaw('LOWER(name) = ?', ['cash in hand'])
            ->value('id')
            ?? (clone $base)
                ->whereRaw('LOWER(name) LIKE ?', ['%cash in hand%'])
                ->value('id')
            ?? (clone $base)
                ->whereRaw('LOWER(name) LIKE ?', ['%cash%'])
                ->orderBy('code')
                ->value('id')
            ?? $base->orderBy('code')->value('id');
    }
}
