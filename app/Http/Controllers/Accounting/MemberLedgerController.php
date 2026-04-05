<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\FinancialPosition;
use App\Models\JournalEntry;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberLedgerController extends Controller
{
    use ResolvesAccountingCompany;

    public function show(Request $request, int $member): Response
    {
        $company = $this->accountingCompany($request);

        $record = Member::query()
            ->where('company_id', $company->id)
            ->findOrFail($member);

        $this->authorize('view', $record);

        $category = $request->query('category', 'all');
        $allowed = ['all', FinancialPosition::CATEGORY_LOAN, FinancialPosition::CATEGORY_SAVINGS, FinancialPosition::CATEGORY_INVESTMENT];
        if (! in_array($category, $allowed, true)) {
            $category = 'all';
        }

        $entries = JournalEntry::query()
            ->where('company_id', $company->id)
            ->where('member_id', $record->id)
            ->when(
                $category !== 'all',
                fn ($q) => $q->where('finance_category', $category)
            )
            ->with([
                'lines' => fn ($q) => $q->orderBy('id'),
                'lines.chartAccount:id,code,name',
            ])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (JournalEntry $e) => [
                'id' => $e->id,
                'transaction_date' => $e->transaction_date->toDateString(),
                'memo' => $e->memo,
                'reference' => $e->reference,
                'status' => $e->status,
                'finance_category' => $e->finance_category,
                'lines' => $e->lines->map(fn ($line) => [
                    'chart_label' => $line->chartAccount
                        ? $line->chartAccount->code.' — '.$line->chartAccount->name
                        : '—',
                    'debit_cents' => (int) $line->debit_cents,
                    'credit_cents' => (int) $line->credit_cents,
                ])->all(),
            ]);

        return Inertia::render('Accounting/Members/Ledger', [
            'member' => [
                'id' => $record->id,
                'member_number' => $record->member_number,
                'name' => $record->name,
                'status' => $record->status,
            ],
            'category' => $category,
            'entries' => $entries,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }
}
