<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\AccountingAuditLog;
use App\Models\ChartAccount;
use App\Models\JournalEntry;
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

            return Inertia::render('Dashboard', [
                'stats' => ['accounts' => 0, 'journal_entries' => 0],
                'readOnly' => true,
                'role' => $user->role,
                'endUserPortal' => [
                    'state' => $user->memberPortalState(),
                    'can_view_finance' => $user->canViewMemberFinancePortal(),
                ],
                'financialRatios' => $financialRatios,
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
}
