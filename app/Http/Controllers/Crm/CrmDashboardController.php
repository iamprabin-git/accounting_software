<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Crm\Concerns\MapsCrmSubjects;
use App\Models\CrmAccount;
use App\Models\CrmActivity;
use App\Models\CrmContact;
use App\Models\CrmOpportunity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrmDashboardController extends Controller
{
    use MapsCrmSubjects;
    use ResolvesAccountingCompany;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CrmAccount::class);

        $company = $this->accountingCompany($request);
        $cid = $company->id;

        $closed = [CrmOpportunity::STAGE_WON, CrmOpportunity::STAGE_LOST];

        $stats = [
            'accounts' => CrmAccount::query()->where('company_id', $cid)->count(),
            'contacts' => CrmContact::query()->where('company_id', $cid)->count(),
            'open_opportunities' => CrmOpportunity::query()
                ->where('company_id', $cid)
                ->whereNotIn('stage', $closed)
                ->count(),
            'pipeline_value_cents' => (int) CrmOpportunity::query()
                ->where('company_id', $cid)
                ->whereNotIn('stage', $closed)
                ->sum('amount_cents'),
        ];

        $pipelineByStage = CrmOpportunity::query()
            ->where('company_id', $cid)
            ->whereNotIn('stage', $closed)
            ->selectRaw('stage, COUNT(*) as cnt, COALESCE(SUM(amount_cents), 0) as total_cents')
            ->groupBy('stage')
            ->get()
            ->map(fn ($r) => [
                'stage' => $r->stage,
                'label' => CrmOpportunity::stageLabels()[$r->stage] ?? $r->stage,
                'count' => (int) $r->cnt,
                'total_cents' => (int) $r->total_cents,
            ]);

        $upcomingActivities = CrmActivity::query()
            ->where('company_id', $cid)
            ->whereNull('completed_at')
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addDays(14))
            ->with('subject')
            ->orderBy('due_at')
            ->limit(12)
            ->get()
            ->map(fn (CrmActivity $a) => [
                'id' => $a->id,
                'type' => $a->type,
                'type_label' => CrmActivity::typeLabels()[$a->type] ?? $a->type,
                'title' => $a->title,
                'due_at' => $a->due_at?->toIso8601String(),
                'subject_type' => $a->subject_type,
                'subject_label' => $this->crmSubjectLabel($a->subject),
            ]);

        $recentWon = CrmOpportunity::query()
            ->where('company_id', $cid)
            ->where('stage', CrmOpportunity::STAGE_WON)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'name', 'amount_cents', 'updated_at'])
            ->map(fn (CrmOpportunity $o) => [
                'id' => $o->id,
                'name' => $o->name,
                'amount_cents' => $o->amount_cents !== null ? (int) $o->amount_cents : null,
                'updated_at' => $o->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('Crm/Dashboard', [
            'stats' => $stats,
            'pipelineByStage' => $pipelineByStage,
            'upcomingActivities' => $upcomingActivities,
            'recentWon' => $recentWon,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }
}
