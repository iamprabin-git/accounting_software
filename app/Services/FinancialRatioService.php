<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class FinancialRatioService
{
    public function __construct(
        protected int $companyId
    ) {}

    /**
     * Ratios for dashboard: profit and loss year-to-date through $asOf, balance sheet as of $asOf.
     *
     * @return array{
     *     as_of: string,
     *     pl_from: string,
     *     pl_to: string,
     *     report_route_params: array{balance_sheet: array<string, string>, profit_loss: array<string, string>, cash_flow: array<string, string>},
     *     items: list<array{key: string, format: 'percent'|'ratio', value: float|null, report_refs: list<string>}>
     * }
     */
    public function forDashboard(?CarbonInterface $asOf = null): array
    {
        $asOf = $asOf ?? Carbon::now()->startOfDay();
        $plTo = $asOf->copy()->startOfDay();
        $plFrom = $plTo->copy()->startOfYear();

        $reports = new AccountingReportService($this->companyId);
        $bs = $reports->balanceSheet($asOf);
        $pl = $reports->profitAndLoss($plFrom, $plTo);

        $assets = (int) $bs['assets_total_cents'];
        $liabilities = (int) array_sum(array_column($bs['liabilities'], 'balance_cents'));
        $equityBook = (int) array_sum(array_column($bs['equity'], 'balance_cents'));
        $retained = (int) $bs['retained_earnings_cents'];
        $totalEquity = $equityBook + $retained;

        $revenue = (int) array_sum(array_column($pl['revenue'], 'amount_cents'));
        $expenses = (int) array_sum(array_column($pl['expenses'], 'amount_cents'));
        $netIncome = (int) $pl['net_income_cents'];

        $cashLikeCents = 0;
        foreach ($bs['assets'] as $row) {
            if ($this->isCashLikeName((string) $row['name'])) {
                $cashLikeCents += (int) $row['balance_cents'];
            }
        }

        $items = [];

        if ($revenue > 0) {
            $items[] = [
                'key' => 'net_profit_margin',
                'format' => 'percent',
                'value' => $netIncome / $revenue,
                'report_refs' => ['profit_loss'],
            ];
            $items[] = [
                'key' => 'expense_ratio',
                'format' => 'percent',
                'value' => $expenses / $revenue,
                'report_refs' => ['profit_loss'],
            ];
        }

        if ($totalEquity > 0) {
            $items[] = [
                'key' => 'debt_to_equity',
                'format' => 'ratio',
                'value' => $liabilities / $totalEquity,
                'report_refs' => ['balance_sheet'],
            ];
        }

        if ($assets > 0) {
            $items[] = [
                'key' => 'debt_to_assets',
                'format' => 'percent',
                'value' => $liabilities / $assets,
                'report_refs' => ['balance_sheet'],
            ];
            $items[] = [
                'key' => 'equity_ratio',
                'format' => 'percent',
                'value' => $totalEquity / $assets,
                'report_refs' => ['balance_sheet'],
            ];
            $items[] = [
                'key' => 'cash_to_assets',
                'format' => 'percent',
                'value' => $cashLikeCents / $assets,
                'report_refs' => ['balance_sheet', 'cash_flow'],
            ];
            $items[] = [
                'key' => 'return_on_assets',
                'format' => 'percent',
                'value' => $netIncome / $assets,
                'report_refs' => ['profit_loss', 'balance_sheet'],
            ];
        }

        if ($totalEquity > 0) {
            $items[] = [
                'key' => 'return_on_equity',
                'format' => 'percent',
                'value' => $netIncome / $totalEquity,
                'report_refs' => ['profit_loss', 'balance_sheet'],
            ];
        }

        return [
            'as_of' => $asOf->toDateString(),
            'pl_from' => $plFrom->toDateString(),
            'pl_to' => $plTo->toDateString(),
            'report_route_params' => [
                'balance_sheet' => ['as_of' => $asOf->toDateString()],
                'profit_loss' => [
                    'from' => $plFrom->toDateString(),
                    'to' => $plTo->toDateString(),
                ],
                'cash_flow' => [
                    'from' => $plFrom->toDateString(),
                    'to' => $plTo->toDateString(),
                ],
            ],
            'items' => $items,
        ];
    }

    protected function isCashLikeName(string $name): bool
    {
        $n = strtolower($name);

        return str_contains($n, 'cash')
            || str_contains($n, 'bank')
            || str_contains($n, 'checking')
            || str_contains($n, 'savings');
    }
}
