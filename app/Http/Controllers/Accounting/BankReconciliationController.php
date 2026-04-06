<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Controller;
use App\Models\BankReconciliationBatch;
use App\Models\BankStatementLine;
use App\Models\BankStatementLineMatch;
use App\Models\ChartAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\AccountingAuditService;
use App\Services\BankFeed\BankFeedRegistry;
use App\Services\BankReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class BankReconciliationController extends Controller
{
    use ResolvesAccountingCompany;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);

        $batches = BankReconciliationBatch::query()
            ->where('company_id', $company->id)
            ->with(['chartAccount:id,code,name', 'user:id,name'])
            ->withCount([
                'statementLines as lines_count',
                'statementLines as matched_count' => fn ($q) => $q->whereNotNull('reconciled_at'),
            ])
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (BankReconciliationBatch $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'created_at' => $b->created_at?->toIso8601String(),
                'chart_account' => $b->chartAccount
                    ? ['id' => $b->chartAccount->id, 'code' => $b->chartAccount->code, 'name' => $b->chartAccount->name]
                    : null,
                'user_name' => $b->user?->name,
                'lines_count' => $b->lines_count,
                'matched_count' => $b->matched_count,
            ]);

        return Inertia::render('Accounting/BankReconciliation/Index', [
            'batches' => $batches,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);

        $accounts = ChartAccount::query()
            ->where('company_id', $company->id)
            ->approvedForJournals()
            ->where('type', ChartAccount::TYPE_ASSET)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (ChartAccount $a) => [
                'id' => $a->id,
                'label' => $a->code.' — '.$a->name,
            ]);

        return Inertia::render('Accounting/BankReconciliation/Create', [
            'bankAccounts' => $accounts,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'csvHint' => 'Header row required. Date column required. Use either a signed amount column (amount, net, value) or separate debit and credit columns (money in − money out). Tab- or comma-separated. Optional: description, reference.',
            'bankFeedProviders' => BankFeedRegistry::providersForFrontend(),
        ]);
    }

    public function store(Request $request, BankReconciliationService $service): RedirectResponse
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'chart_account_id' => ['required', 'integer', 'exists:chart_accounts,id'],
            'name' => ['nullable', 'string', 'max:160'],
            'statement_opening_balance' => ['nullable', 'string', 'max:32'],
            'statement_closing_balance' => ['nullable', 'string', 'max:32'],
            'csv' => ['required_without:csv_file', 'nullable', 'string'],
            'csv_file' => ['required_without:csv', 'nullable', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $account = ChartAccount::query()->findOrFail((int) $validated['chart_account_id']);
        try {
            $service->assertChartAccountForCompany($account, $company->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['chart_account_id' => $e->getMessage()])->withInput();
        }

        $openingCents = $service->parseOptionalBalanceCents($validated['statement_opening_balance'] ?? null);
        $closingCents = $service->parseOptionalBalanceCents($validated['statement_closing_balance'] ?? null);

        $raw = $validated['csv'] ?? '';
        if ($request->hasFile('csv_file')) {
            $raw = (string) file_get_contents($request->file('csv_file')->getRealPath());
        }

        try {
            $rows = $service->parseCsv($raw);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['csv' => $e->getMessage()])->withInput();
        }

        $variance = $service->balanceCheckVariance($openingCents, $closingCents, $rows);

        $batch = BankReconciliationBatch::query()->create([
            'company_id' => $company->id,
            'chart_account_id' => $account->id,
            'user_id' => $request->user()?->id,
            'name' => isset($validated['name']) && $validated['name'] !== '' ? $validated['name'] : null,
            'statement_opening_balance_cents' => $openingCents,
            'statement_closing_balance_cents' => $closingCents,
        ]);

        foreach ($rows as $row) {
            BankStatementLine::query()->create([
                'bank_reconciliation_batch_id' => $batch->id,
                'transaction_date' => $row['transaction_date'],
                'amount_cents' => $row['amount_cents'],
                'description' => $row['description'],
                'external_reference' => $row['external_reference'],
            ]);
        }

        app(AccountingAuditService::class)->logJournalAction(
            companyId: $company->id,
            journalEntryId: null,
            action: 'bank_reconciliation.import',
            actor: $request->user(),
            metadata: [
                'batch_id' => $batch->id,
                'chart_account_id' => $account->id,
                'lines_imported' => count($rows),
                'opening_cents' => $openingCents,
                'closing_cents' => $closingCents,
                'balance_variance_cents' => $variance,
            ],
            request: $request,
        );

        $query = $request->user()->isAdmin() ? ['company_id' => $company->id] : [];

        $redirect = redirect()
            ->route('bank-reconciliation.show', array_merge(['batch' => $batch->id], $query))
            ->with('status', __('Imported :n statement lines.', ['n' => count($rows)]));

        if ($variance !== null && $variance !== 0) {
            $redirect->with(
                'balance_warning',
                __('Statement balance check: expected closing differs from entered closing by :v.', [
                    'v' => number_format($variance / 100, 2),
                ]),
            );
        }

        return $redirect;
    }

    public function show(Request $request, BankReconciliationBatch $batch, BankReconciliationService $service): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);
        abort_unless((int) $batch->company_id === $company->id, 404);

        $batch->load(['chartAccount:id,code,name']);

        $sumLines = (int) $batch->statementLines()->sum('amount_cents');
        $opening = $batch->statement_opening_balance_cents;
        $closing = $batch->statement_closing_balance_cents;
        $expectedClosing = $opening !== null ? $opening + $sumLines : null;
        $balanceVariance = ($opening !== null && $closing !== null)
            ? ($expectedClosing - $closing)
            : null;

        $candidates = $service->unmatchedJournalLinesForAccount($company->id, (int) $batch->chart_account_id);

        $lines = $batch->statementLines()
            ->with([
                'matches.journalLine.journalEntry',
            ])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->map(function (BankStatementLine $line) use ($service, $candidates) {
                $matchedSum = $service->matchedNetSum($line);
                $remaining = (int) $line->amount_cents - $matchedSum;
                $suggestions = $line->isFullyMatched() ? [] : $service->suggestJournalLines($line, $candidates);

                $journalMatches = $line->matches->map(function (BankStatementLineMatch $m) {
                    $jl = $m->journalLine;

                    return [
                        'match_id' => $m->id,
                        'journal_line_id' => $jl?->id,
                        'journal_entry_id' => $jl?->journal_entry_id,
                        'posted_number' => $jl?->journalEntry?->posted_number,
                        'net_cents' => $jl ? $jl->netAmountCentsForBankAccount() : 0,
                    ];
                })->values()->all();

                return [
                    'id' => $line->id,
                    'transaction_date' => $line->transaction_date->toDateString(),
                    'amount_cents' => (int) $line->amount_cents,
                    'matched_sum_cents' => $matchedSum,
                    'remaining_cents' => $remaining,
                    'description' => $line->description,
                    'external_reference' => $line->external_reference,
                    'matched' => $line->isFullyMatched(),
                    'reconciled_at' => $line->reconciled_at?->toIso8601String(),
                    'journal_matches' => $journalMatches,
                    'suggestions' => $suggestions,
                ];
            });

        $unmatchedGl = $candidates->take(100)->map(fn (JournalLine $jl) => [
            'id' => $jl->id,
            'journal_entry_id' => $jl->journal_entry_id,
            'transaction_date' => $jl->journalEntry?->transaction_date?->toDateString(),
            'net_cents' => $jl->netAmountCentsForBankAccount(),
            'description' => $jl->description ?: $jl->journalEntry?->memo,
            'reference' => $jl->journalEntry?->reference,
            'posted_number' => $jl->journalEntry?->posted_number,
        ]);

        return Inertia::render('Accounting/BankReconciliation/Show', [
            'batch' => [
                'id' => $batch->id,
                'name' => $batch->name,
                'created_at' => $batch->created_at?->toIso8601String(),
                'statement_opening_balance_cents' => $batch->statement_opening_balance_cents,
                'statement_closing_balance_cents' => $batch->statement_closing_balance_cents,
                'sum_statement_lines_cents' => $sumLines,
                'expected_closing_cents' => $expectedClosing,
                'balance_variance_cents' => $balanceVariance,
                'chart_account' => $batch->chartAccount
                    ? [
                        'id' => $batch->chartAccount->id,
                        'code' => $batch->chartAccount->code,
                        'name' => $batch->chartAccount->name,
                    ]
                    : null,
            ],
            'lines' => $lines,
            'unmatchedGlPreview' => $unmatchedGl,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'bankFeedProviders' => BankFeedRegistry::providersForFrontend(),
        ]);
    }

    public function match(Request $request, BankReconciliationBatch $batch, BankReconciliationService $service): RedirectResponse
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);
        abort_unless((int) $batch->company_id === $company->id, 404);

        $validated = $request->validate([
            'bank_statement_line_id' => ['required', 'integer', 'exists:bank_statement_lines,id'],
            'journal_line_id' => ['required', 'integer', 'exists:journal_lines,id'],
        ]);

        $stmt = BankStatementLine::query()
            ->where('bank_reconciliation_batch_id', $batch->id)
            ->findOrFail((int) $validated['bank_statement_line_id']);
        $stmt->load('batch');

        $line = JournalLine::query()->with('journalEntry')->findOrFail((int) $validated['journal_line_id']);

        try {
            $service->addMatch($stmt, $line);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['match' => $e->getMessage()]);
        }

        app(AccountingAuditService::class)->logJournalAction(
            companyId: $company->id,
            journalEntryId: (int) $line->journal_entry_id,
            action: 'bank_reconciliation.matched',
            actor: $request->user(),
            metadata: [
                'batch_id' => $batch->id,
                'bank_statement_line_id' => $stmt->id,
                'journal_line_id' => $line->id,
            ],
            request: $request,
        );

        return back()->with('status', __('Match added.'));
    }

    public function removeMatch(
        Request $request,
        BankReconciliationBatch $batch,
        BankStatementLineMatch $match,
        BankReconciliationService $service,
    ): RedirectResponse {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);
        abort_unless((int) $batch->company_id === $company->id, 404);

        $match->load(['bankStatementLine', 'journalLine']);
        $stmt = $match->bankStatementLine;
        abort_unless($stmt && (int) $stmt->bank_reconciliation_batch_id === $batch->id, 404);

        $journalEntryId = (int) ($match->journalLine?->journal_entry_id ?? 0);
        $matchId = $match->id;
        $stmtId = $stmt->id;

        $service->removeMatch($match);

        if ($journalEntryId > 0) {
            app(AccountingAuditService::class)->logJournalAction(
                companyId: $company->id,
                journalEntryId: $journalEntryId,
                action: 'bank_reconciliation.match_removed',
                actor: $request->user(),
                metadata: [
                    'batch_id' => $batch->id,
                    'bank_statement_line_id' => $stmtId,
                    'bank_statement_line_match_id' => $matchId,
                ],
                request: $request,
            );
        }

        return back()->with('status', __('Match removed.'));
    }

    public function unmatch(Request $request, BankReconciliationBatch $batch, BankStatementLine $statementLine, BankReconciliationService $service): RedirectResponse
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);
        abort_unless((int) $batch->company_id === $company->id, 404);
        abort_unless((int) $statementLine->bank_reconciliation_batch_id === $batch->id, 404);

        $entryIds = $statementLine->matches()->pluck('journal_line_id')->map(
            fn ($jlId) => (int) JournalLine::query()->whereKey($jlId)->value('journal_entry_id'),
        )->filter()->unique()->values()->all();

        $service->clearAllMatches($statementLine);

        foreach ($entryIds as $journalEntryId) {
            app(AccountingAuditService::class)->logJournalAction(
                companyId: $company->id,
                journalEntryId: $journalEntryId,
                action: 'bank_reconciliation.unmatched',
                actor: $request->user(),
                metadata: [
                    'batch_id' => $batch->id,
                    'bank_statement_line_id' => $statementLine->id,
                ],
                request: $request,
            );
        }

        return back()->with('status', __('All matches cleared for this line.'));
    }

    public function autoMatch(Request $request, BankReconciliationBatch $batch, BankReconciliationService $service): RedirectResponse
    {
        $this->authorize('viewAny', JournalEntry::class);

        $company = $this->accountingCompany($request);
        abort_unless((int) $batch->company_id === $company->id, 404);

        $n = $service->autoMatchBatch($batch);

        app(AccountingAuditService::class)->logJournalAction(
            companyId: $company->id,
            journalEntryId: null,
            action: 'bank_reconciliation.auto_matched',
            actor: $request->user(),
            metadata: [
                'batch_id' => $batch->id,
                'matched_count' => $n,
            ],
            request: $request,
        );

        return back()->with('status', __('Auto-matched :n lines (unique amount + date within 3 days).', ['n' => $n]));
    }

    public function fetchFeed(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', JournalEntry::class);

        if (! BankFeedRegistry::anyProviderReady()) {
            return back()->with(
                'error',
                __('No bank feed API is configured. Set Plaid or TrueLayer credentials in .env and wire the provider to push transactions into the CSV importer.'),
            );
        }

        return back()->with(
            'error',
            __('Bank feed pull is not implemented yet. Export CSV from your bank or provider dashboard and import it here.'),
        );
    }
}
