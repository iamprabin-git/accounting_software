<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\AccountingAuditLog;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\TellerDayClose;
use App\Services\AccountingReportService;
use App\Services\FinancialRatioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ResolvesAccountingCompany;

    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isEndUser()) {
            $financialRatios = null;
            if ($user->company_id && $user->memberPortalState() === 'ok') {
                $financialRatios = $this->makeFinancialRatiosPayload(
                    companyId: $user->company_id,
                    canOpenReports: false,
                    forAdmin: false,
                );
            }

            $paymentInfo = ['visible' => false, 'bank_payment_details' => null, 'payment_qr_url' => null];
            $company = $this->optionalAccountingCompany($request);
            if ($company !== null) {
                $paymentInfo = $this->companyPaymentDetailsForPortal($company);
            }

            return Inertia::render('Dashboard', [
                'stats' => ['accounts' => 0, 'journal_entries' => 0],
                'readOnly' => true,
                'role' => $user->role,
                'endUserPortal' => [
                    'state' => $user->memberPortalState(),
                    'can_view_finance' => $user->canViewMemberFinancePortal(),
                ],
                'financialRatios' => $financialRatios,
                'payment_info' => $paymentInfo,
            ]);
        }

        if ($user->role === 'staff' && $user->canEditAccounting()) {
            $company = $this->optionalAccountingCompany($request);
            if ($company !== null && $company->allowsFinanceSuite()) {
                $query = $user->isAdmin() ? ['company_id' => $company->id] : [];

                return redirect()->route('teller.day-close.create', $query);
            }
        }

        $accountsQuery = ChartAccount::query();
        $entriesQuery = JournalEntry::query();

        if (! $user->isAdmin()) {
            $accountsQuery->forUser($user);
            $entriesQuery->forUser($user);
        }

        $financialRatios = null;
        $approvalSla = null;
        $auditIntegrityAlert = null;
        $auditIntegrityTrend = null;
        $controlCenter = null;
        $systemHealth = null;
        $approvalInbox = null;
        if ($user->canViewAccountingReports()) {
            $company = $this->optionalAccountingCompany($request);
            if ($company !== null) {
                $financialRatios = $this->makeFinancialRatiosPayload(
                    companyId: $company->id,
                    canOpenReports: true,
                    forAdmin: $user->isAdmin(),
                );
                $approvalSla = $this->makeApprovalSlaPayload(
                    companyId: $company->id,
                    forAdmin: $user->isAdmin(),
                );
                $auditIntegrityAlert = $this->makeAuditIntegrityAlertPayload(
                    companyId: $company->id,
                    forAdmin: $user->isAdmin(),
                );
                $auditIntegrityTrend = $this->makeAuditIntegrityTrendPayload(
                    companyId: $company->id,
                    forAdmin: $user->isAdmin(),
                );
                $controlCenter = $this->makeControlCenterPayload(
                    companyId: $company->id,
                    forAdmin: $user->isAdmin(),
                );
                $systemHealth = $this->makeSystemHealthPayload(
                    companyId: $company->id,
                    forAdmin: $user->isAdmin(),
                );
                $approvalInbox = $this->makeApprovalInboxPayload(
                    companyId: $company->id,
                    forAdmin: $user->isAdmin(),
                );
            }
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'accounts' => $accountsQuery->count(),
                'journal_entries' => $entriesQuery->count(),
            ],
            'readOnly' => false,
            'role' => $user->role,
            'endUserPortal' => null,
            'financialRatios' => $financialRatios,
            'approvalSla' => $approvalSla,
            'auditIntegrityAlert' => $auditIntegrityAlert,
            'auditIntegrityTrend' => $auditIntegrityTrend,
            'controlCenter' => $controlCenter,
            'systemHealth' => $systemHealth,
            'approvalInbox' => $approvalInbox,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function makeFinancialRatiosPayload(int $companyId, bool $canOpenReports, bool $forAdmin): ?array
    {
        $snap = (new FinancialRatioService($companyId))->forDashboard();

        return array_merge($snap, [
            'can_open_reports' => $canOpenReports,
            'admin_company_id' => $forAdmin ? $companyId : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeApprovalSlaPayload(int $companyId, bool $forAdmin): array
    {
        $base = JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('status', JournalEntry::STATUS_PENDING);

        $over2 = (clone $base)->where('submitted_at', '<=', now()->subDays(2))->count();
        $over7 = (clone $base)->where('submitted_at', '<=', now()->subDays(7))->count();
        $pendingTotal = (clone $base)->count();

        $oldest = (clone $base)
            ->with('firstApprovedBy:id,name')
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->first();

        return [
            'pending_total' => $pendingTotal,
            'over_2_days' => $over2,
            'over_7_days' => $over7,
            'oldest_pending' => $oldest ? [
                'id' => $oldest->id,
                'submitted_at' => $oldest->submitted_at?->toIso8601String(),
                'pending_age_days' => $oldest->submitted_at
                    ? (int) $oldest->submitted_at->copy()->startOfDay()->diffInDays(now()->startOfDay())
                    : null,
                'first_approved_by_name' => $oldest->firstApprovedBy?->name,
            ] : null,
            'admin_company_id' => $forAdmin ? $companyId : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function makeAuditIntegrityAlertPayload(int $companyId, bool $forAdmin): ?array
    {
        $latest = AccountingAuditLog::query()
            ->where('company_id', $companyId)
            ->where('action', 'like', 'audit.integrity_%')
            ->latest('id')
            ->first();

        if (! $latest) {
            return null;
        }

        $valid = (bool) (($latest->metadata['valid'] ?? true) === true);
        if ($valid) {
            return null;
        }

        return [
            'action' => $latest->action,
            'created_at' => $latest->created_at?->toIso8601String(),
            'first_broken_event_id' => $latest->metadata['first_broken_event_id'] ?? null,
            'reason' => $latest->metadata['first_broken_reason'] ?? null,
            'admin_company_id' => $forAdmin ? $companyId : null,
        ];
    }

    /**
     * Last 7 scheduled (nightly) integrity checks, oldest-first for timeline display.
     *
     * @return list<array{created_at: string|null, pass: bool, action: string}>
     */
    protected function makeAuditIntegrityTrendPayload(int $companyId, bool $forAdmin): array
    {
        $rows = AccountingAuditLog::query()
            ->where('company_id', $companyId)
            ->whereIn('action', ['audit.integrity_nightly_ok', 'audit.integrity_nightly_failed'])
            ->latest('id')
            ->limit(7)
            ->get(['action', 'created_at']);

        $points = $rows->reverse()->values()->map(fn (AccountingAuditLog $log) => [
            'created_at' => $log->created_at?->toIso8601String(),
            'pass' => $log->action === 'audit.integrity_nightly_ok',
            'action' => $log->action,
        ])->all();

        return [
            'points' => $points,
            'admin_company_id' => $forAdmin ? $companyId : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeControlCenterPayload(int $companyId, bool $forAdmin): array
    {
        $today = now()->toDateString();

        $pendingMembers = Member::query()
            ->where('company_id', $companyId)
            ->where('status', Member::STATUS_PENDING)
            ->count();

        $pendingChartAccounts = ChartAccount::query()
            ->where('company_id', $companyId)
            ->where('approval_status', ChartAccount::STATUS_PENDING)
            ->count();

        $pendingJournals = JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('status', JournalEntry::STATUS_PENDING)
            ->count();

        $openTellerDay = TellerDayClose::query()
            ->where('company_id', $companyId)
            ->whereDate('close_date', $today)
            ->where('day_status', TellerDayClose::STATUS_OPEN)
            ->latest('id')
            ->first();

        $teller = [
            'is_open' => $openTellerDay !== null,
            'close_date' => $openTellerDay?->close_date?->toDateString(),
            'closing_error_cents' => (int) ($openTellerDay?->closing_error_cents ?? 0),
            'cash_received_cents' => (int) ($openTellerDay?->cash_received_cents ?? 0),
            'system_cash_cents' => (int) ($openTellerDay?->system_cash_cents ?? 0),
        ];

        return [
            'pending' => [
                'members' => $pendingMembers,
                'chart_accounts' => $pendingChartAccounts,
                'journals' => $pendingJournals,
                'total' => $pendingMembers + $pendingChartAccounts + $pendingJournals,
            ],
            'teller' => $teller,
            'admin_company_id' => $forAdmin ? $companyId : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeSystemHealthPayload(int $companyId, bool $forAdmin): array
    {
        $company = Company::query()->findOrFail($companyId);
        $tb = (new AccountingReportService($companyId))->trialBalance(
            asOf: now(),
            showZero: true,
        );
        $tbDebit = (int) ($tb['totals']['debit_cents'] ?? 0);
        $tbCredit = (int) ($tb['totals']['credit_cents'] ?? 0);
        $tbDelta = $tbDebit - $tbCredit;

        $lockDate = $company->journal_lock_date?->toDateString();
        $lockAgeDays = $company->journal_lock_date
            ? (int) $company->journal_lock_date->diffInDays(now())
            : null;

        return [
            'trial_balance' => [
                'debit_cents' => $tbDebit,
                'credit_cents' => $tbCredit,
                'delta_cents' => $tbDelta,
                'is_balanced' => $tbDelta === 0,
            ],
            'period_lock' => [
                'lock_date' => $lockDate,
                'lock_reason' => $company->journal_lock_reason,
                'last_close_type' => $company->last_period_close_type,
                'lock_age_days' => $lockAgeDays,
                'is_set' => $lockDate !== null,
            ],
            'admin_company_id' => $forAdmin ? $companyId : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeApprovalInboxPayload(int $companyId, bool $forAdmin): array
    {
        $pendingMembers = Member::query()
            ->where('company_id', $companyId)
            ->where('status', Member::STATUS_PENDING)
            ->orderBy('created_at')
            ->limit(5)
            ->get(['id', 'member_number', 'name', 'created_at'])
            ->map(fn (Member $m) => [
                'id' => $m->id,
                'label' => '#'.($m->member_number ?? '—').' '.$m->name,
                'created_at' => $m->created_at?->toIso8601String(),
            ])
            ->all();

        $pendingChartAccounts = ChartAccount::query()
            ->where('company_id', $companyId)
            ->where('approval_status', ChartAccount::STATUS_PENDING)
            ->orderBy('created_at')
            ->limit(5)
            ->get(['id', 'code', 'name', 'created_at'])
            ->map(fn (ChartAccount $a) => [
                'id' => $a->id,
                'label' => trim(($a->code ? $a->code.' ' : '').$a->name),
                'created_at' => $a->created_at?->toIso8601String(),
            ])
            ->all();

        $pendingJournals = JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('status', JournalEntry::STATUS_PENDING)
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'reference', 'submitted_at'])
            ->map(fn (JournalEntry $j) => [
                'id' => $j->id,
                'label' => 'Journal #'.$j->id.($j->reference ? ' · '.$j->reference : ''),
                'created_at' => $j->submitted_at?->toIso8601String(),
            ])
            ->all();

        return [
            'members' => $pendingMembers,
            'chart_accounts' => $pendingChartAccounts,
            'journals' => $pendingJournals,
            'admin_company_id' => $forAdmin ? $companyId : null,
        ];
    }
}
