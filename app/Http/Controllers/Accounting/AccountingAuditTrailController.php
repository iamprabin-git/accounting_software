<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Controller;
use App\Models\AccountingAuditLog;
use App\Services\AccountingAuditService;
use App\Services\AuditIntegrityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingAuditTrailController extends Controller
{
    use ResolvesAccountingCompany;

    public function index(Request $request): Response
    {
        abort_unless($request->user()->canViewAccountingReports(), 403);

        $company = $this->accountingCompany($request);
        $filters = $this->validatedFilters($request);

        $query = $this->applyFilters(
            AccountingAuditLog::query()
                ->where('company_id', $company->id)
                ->with(['user:id,name']),
            $filters,
        );

        $logs = $query->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AccountingAuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'actor_name' => $log->user?->name ?? 'System',
                'actor_ip' => $log->actor_ip,
                'journal_entry_id' => $log->journal_entry_id,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at?->toIso8601String(),
                'event_hash' => $log->event_hash,
                'previous_event_hash' => $log->previous_event_hash,
            ]);

        $summaryBase = AccountingAuditLog::query()
            ->where('company_id', $company->id);
        $failedIntegrityCount = (clone $summaryBase)
            ->whereIn('action', ['audit.integrity_failed', 'audit.integrity_nightly_failed'])
            ->count();
        $todayEvents = (clone $summaryBase)
            ->whereDate('created_at', now()->toDateString())
            ->count();
        $uniqueActors7d = (clone $summaryBase)
            ->whereDate('created_at', '>=', now()->subDays(7)->toDateString())
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        return Inertia::render('Accounting/AuditTrail/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'action_options' => AccountingAuditLog::query()
                ->where('company_id', $company->id)
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action')
                ->values()
                ->all(),
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
            'integrity' => app(AuditIntegrityService::class)->verifyCompany($company->id),
            'last_verification' => AccountingAuditLog::query()
                ->where('company_id', $company->id)
                ->where('action', 'like', 'audit.integrity_%')
                ->latest('id')
                ->first(['id', 'action', 'metadata', 'created_at'])
                ?->only(['id', 'action', 'metadata', 'created_at']),
            'summary' => [
                'today_events' => $todayEvents,
                'failed_integrity_events' => $failedIntegrityCount,
                'unique_actors_7d' => $uniqueActors7d,
            ],
        ]);
    }

    public function verifyNow(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canViewAccountingReports(), 403);

        $company = $this->accountingCompany($request);
        $integrity = app(AuditIntegrityService::class)->verifyCompany($company->id);
        $signature = app(AuditIntegrityService::class)->verificationSignature(
            companyId: $company->id,
            mode: 'manual',
            result: $integrity,
            actorUserId: $request->user()->id,
        );

        app(AccountingAuditService::class)->logJournalAction(
            companyId: $company->id,
            journalEntryId: null,
            action: $integrity['valid'] ? 'audit.integrity_verified' : 'audit.integrity_failed',
            actor: $request->user(),
            metadata: [
                ...$integrity,
                'mode' => 'manual',
                'signature' => $signature,
            ],
            request: $request,
        );

        if (! $integrity['valid']) {
            app(AuditIntegrityService::class)->notifyFailure($company, $integrity, 'manual');
        }

        $query = $request->user()->isAdmin() ? ['company_id' => $company->id] : [];

        return redirect()
            ->route('audit-trail.index', $query)
            ->with(
                'status',
                $integrity['valid']
                    ? __('Integrity check passed.')
                    : __('Integrity check failed. Alert emails have been sent.'),
            );
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless($request->user()->canViewAccountingReports(), 403);
        $company = $this->accountingCompany($request);
        $filters = $this->validatedFilters($request);

        $query = $this->applyFilters(
            AccountingAuditLog::query()
                ->where('company_id', $company->id)
                ->with(['user:id,name'])
                ->orderBy('id'),
            $filters,
        );

        $filename = 'audit-trail-'.$company->id.'-'.now()->format('YmdHis').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id',
                'created_at',
                'action',
                'actor_name',
                'actor_ip',
                'journal_entry_id',
                'event_hash',
                'previous_event_hash',
                'metadata_json',
            ]);

            foreach ($query->cursor() as $log) {
                fputcsv($out, [
                    $log->id,
                    $log->created_at?->toIso8601String(),
                    $log->action,
                    $log->user?->name ?? 'System',
                    $log->actor_ip,
                    $log->journal_entry_id,
                    $log->event_hash,
                    $log->previous_event_hash,
                    json_encode($log->metadata ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPrintable(Request $request)
    {
        abort_unless($request->user()->canViewAccountingReports(), 403);
        $company = $this->accountingCompany($request);
        $filters = $this->validatedFilters($request);

        $query = $this->applyFilters(
            AccountingAuditLog::query()
                ->where('company_id', $company->id)
                ->with(['user:id,name'])
                ->orderByDesc('id'),
            $filters,
        );

        $rows = $query->limit(500)->get();

        return response()->view('exports.audit-trail', [
            'company' => $company,
            'rows' => $rows,
            'filters' => $filters,
            'generatedAt' => now()->toIso8601String(),
        ]);
    }

    /**
     * @return array{action: ?string, action_like: ?string, from_date: ?string, to_date: ?string, journal_entry_id: ?int, actor_name: ?string, hash: ?string}
     */
    private function validatedFilters(Request $request): array
    {
        $v = $request->validate([
            'action' => ['nullable', 'string', 'max:64'],
            'action_like' => ['nullable', 'string', 'max:64'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'journal_entry_id' => ['nullable', 'integer'],
            'actor_name' => ['nullable', 'string', 'max:100'],
            'hash' => ['nullable', 'string', 'max:128'],
        ]);

        return [
            'action' => $v['action'] ?? null,
            'action_like' => $v['action_like'] ?? null,
            'from_date' => $v['from_date'] ?? null,
            'to_date' => $v['to_date'] ?? null,
            'journal_entry_id' => isset($v['journal_entry_id']) ? (int) $v['journal_entry_id'] : null,
            'actor_name' => $v['actor_name'] ?? null,
            'hash' => $v['hash'] ?? null,
        ];
    }

    /**
     * @param  array{action: ?string, action_like: ?string, from_date: ?string, to_date: ?string, journal_entry_id: ?int, actor_name: ?string, hash: ?string}  $filters
     */
    private function applyFilters($query, array $filters)
    {
        if ($filters['action']) {
            $query->where('action', $filters['action']);
        }
        if ($filters['action_like']) {
            $query->where('action', 'like', '%'.$filters['action_like'].'%');
        }
        if ($filters['from_date']) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if ($filters['to_date']) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }
        if ($filters['journal_entry_id']) {
            $query->where('journal_entry_id', $filters['journal_entry_id']);
        }
        if ($filters['actor_name']) {
            $needle = $filters['actor_name'];
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$needle.'%'));
        }
        if ($filters['hash']) {
            $query->where(function ($q) use ($filters) {
                $q->where('event_hash', 'like', '%'.$filters['hash'].'%')
                    ->orWhere('previous_event_hash', 'like', '%'.$filters['hash'].'%');
            });
        }

        return $query;
    }
}
