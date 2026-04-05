<?php

namespace App\Services;

use App\Models\FinancialPosition;
use App\Models\FinancialPositionAccrual;
use Illuminate\Support\Collection;

class FinanceAccrualService
{
    /**
     * Create or refresh unpaid monthly rows for loans (interest due) and savings (interest accrual).
     *
     * @return int Number of months touched (including updates)
     */
    public function syncMonthlyAccrualsForYear(FinancialPosition $position, int $year): int
    {
        if (! $position->usesBankingMonthlyRate()) {
            return 0;
        }

        $kind = $position->category === FinancialPosition::CATEGORY_LOAN
            ? FinancialPositionAccrual::KIND_LOAN_MONTHLY
            : FinancialPositionAccrual::KIND_SAVINGS_MONTHLY;

        $amount = $position->monthlyInterestCents();
        $touched = 0;

        for ($month = 1; $month <= 12; $month++) {
            $existing = FinancialPositionAccrual::query()
                ->where('financial_position_id', $position->id)
                ->where('accrual_year', $year)
                ->where('accrual_month', $month)
                ->where('kind', $kind)
                ->first();

            if ($existing?->isPosted()) {
                continue;
            }

            FinancialPositionAccrual::query()->updateOrCreate(
                [
                    'financial_position_id' => $position->id,
                    'accrual_year' => $year,
                    'accrual_month' => $month,
                    'kind' => $kind,
                ],
                [
                    'company_id' => $position->company_id,
                    'amount_cents' => $amount,
                ]
            );
            $touched++;
        }

        return $touched;
    }

    /**
     * @return Collection<int, FinancialPositionAccrual>
     */
    public function unpaidSavingsAccrualsForQuarter(FinancialPosition $position, int $year, int $quarter): Collection
    {
        $months = match ($quarter) {
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12],
            default => [],
        };

        return FinancialPositionAccrual::query()
            ->where('financial_position_id', $position->id)
            ->where('accrual_year', $year)
            ->whereIn('accrual_month', $months)
            ->where('kind', FinancialPositionAccrual::KIND_SAVINGS_MONTHLY)
            ->whereNull('journal_entry_id')
            ->orderBy('accrual_month')
            ->get();
    }

    public function totalCents(Collection $accruals): int
    {
        return (int) $accruals->sum('amount_cents');
    }
}
