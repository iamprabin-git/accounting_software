<?php

namespace App\Services;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\InventoryItem;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AccountingReportService
{
    public function __construct(
        protected int $companyId
    ) {}

    /**
     * @return Collection<int, JournalLine>
     */
    protected function linesForApprovedEntries(?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        return JournalLine::query()
            ->with(['chartAccount', 'journalEntry'])
            ->whereHas('chartAccount', fn (Builder $q) => $q->where('company_id', $this->companyId))
            ->whereHas('journalEntry', function (Builder $q) use ($from, $to) {
                $q->where('company_id', $this->companyId)
                    ->where('status', JournalEntry::STATUS_APPROVED);

                if ($to !== null) {
                    $q->whereDate('transaction_date', '<=', $to);
                }

                if ($from !== null) {
                    $q->whereDate('transaction_date', '>=', $from);
                }
            })
            ->get();
    }

    /**
     * @return array{accounts: list<array{code: string, name: string, type: string, debit_cents: int, credit_cents: int, inventory_extension?: bool}>, totals: array{debit_cents: int, credit_cents: int}, inventory_at_cost_cents: int}
     */
    public function trialBalance(CarbonInterface $asOf, bool $showZero = true): array
    {
        $lines = $this->linesForApprovedEntries(to: $asOf);

        $rows = $this->aggregateByAccount($lines);
        $coa = $this->approvedAccountsById();

        $accounts = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($coa as $accountId => $account) {
            $row = $rows->get($accountId, [
                'code' => $account['code'],
                'name' => $account['name'],
                'type' => $account['type'],
                'debit_cents' => 0,
                'credit_cents' => 0,
            ]);

            $net = $row['debit_cents'] - $row['credit_cents'];

            if ($net > 0) {
                $debit = $net;
                $credit = 0;
            } elseif ($net < 0) {
                $debit = 0;
                $credit = -$net;
            } else {
                $debit = 0;
                $credit = 0;
            }

            if (! $showZero && $debit === 0 && $credit === 0) {
                continue;
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $accounts[] = [
                'code' => $row['code'],
                'name' => $row['name'],
                'type' => $row['type'],
                'debit_cents' => $debit,
                'credit_cents' => $credit,
            ];
        }

        $inventoryAtCostCents = (int) InventoryItem::query()
            ->where('company_id', $this->companyId)
            ->get()
            ->sum(fn (InventoryItem $item) => $item->valueAtCostCents());

        if ($inventoryAtCostCents > 0) {
            $company = Company::query()->find($this->companyId);
            $linked = null;
            if ($company?->inventory_chart_account_id) {
                $linked = ChartAccount::query()
                    ->whereKey($company->inventory_chart_account_id)
                    ->where('company_id', $this->companyId)
                    ->first();
            }

            $debitLabel = $linked !== null
                ? sprintf('%s %s — %s', $linked->code, $linked->name, __('stock at cost (inventory)'))
                : __('Inventory — stock on hand (at cost)');

            $debitCode = $linked !== null ? $linked->code.'-STK' : 'INV-STK';

            $accounts[] = [
                'code' => $debitCode,
                'name' => $debitLabel,
                'type' => ChartAccount::TYPE_ASSET,
                'debit_cents' => $inventoryAtCostCents,
                'credit_cents' => 0,
                'inventory_extension' => true,
            ];
            $accounts[] = [
                'code' => 'ZZ-INV-OFF',
                'name' => __('Stock valuation offset (informational; keeps trial balance balanced)'),
                'type' => ChartAccount::TYPE_LIABILITY,
                'debit_cents' => 0,
                'credit_cents' => $inventoryAtCostCents,
                'inventory_extension' => true,
            ];
            $totalDebit += $inventoryAtCostCents;
            $totalCredit += $inventoryAtCostCents;
        }

        usort($accounts, fn (array $a, array $b) => strcmp($a['code'], $b['code']));

        return [
            'accounts' => $accounts,
            'totals' => [
                'debit_cents' => $totalDebit,
                'credit_cents' => $totalCredit,
            ],
            'inventory_at_cost_cents' => $inventoryAtCostCents,
        ];
    }

    /**
     * @return array{revenue: list<array{code: string, name: string, amount_cents: int}>, expenses: list<array{code: string, name: string, amount_cents: int}>, net_income_cents: int}
     */
    public function profitAndLoss(CarbonInterface $from, CarbonInterface $to, bool $showZero = true): array
    {
        $lines = $this->linesForApprovedEntries($from, $to);

        $rows = $this->aggregateByAccount($lines);
        $coa = $this->approvedAccountsById();

        $revenue = [];
        $expenses = [];
        $totalRevenue = 0;
        $totalExpense = 0;

        foreach ($coa as $accountId => $account) {
            $row = $rows->get($accountId, [
                'code' => $account['code'],
                'name' => $account['name'],
                'type' => $account['type'],
                'debit_cents' => 0,
                'credit_cents' => 0,
            ]);

            if ($row['type'] === ChartAccount::TYPE_REVENUE) {
                $amt = $row['credit_cents'] - $row['debit_cents'];
                if (! $showZero && $amt === 0) {
                    continue;
                }
                $revenue[] = [
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'amount_cents' => $amt,
                ];
                $totalRevenue += $amt;
            }

            if ($row['type'] === ChartAccount::TYPE_EXPENSE) {
                $amt = $row['debit_cents'] - $row['credit_cents'];
                if (! $showZero && $amt === 0) {
                    continue;
                }
                $expenses[] = [
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'amount_cents' => $amt,
                ];
                $totalExpense += $amt;
            }
        }

        usort($revenue, fn (array $a, array $b) => strcmp($a['code'], $b['code']));
        usort($expenses, fn (array $a, array $b) => strcmp($a['code'], $b['code']));

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_income_cents' => $totalRevenue - $totalExpense,
        ];
    }

    /**
     * @return array{assets: list<array{code: string, name: string, balance_cents: int}>, liabilities: list<array{code: string, name: string, balance_cents: int}>, equity: list<array{code: string, name: string, balance_cents: int}>, liabilities_plus_equity_cents: int, assets_total_cents: int}
     */
    public function balanceSheet(CarbonInterface $asOf, bool $showZero = true): array
    {
        $lines = $this->linesForApprovedEntries(to: $asOf);

        $rows = $this->aggregateByAccount($lines);
        $coa = $this->approvedAccountsById();

        $assets = [];
        $liabilities = [];
        $equity = [];
        $assetsTotal = 0;
        $liabilitiesTotal = 0;
        $equityTotal = 0;

        foreach ($coa as $accountId => $account) {
            $row = $rows->get($accountId, [
                'code' => $account['code'],
                'name' => $account['name'],
                'type' => $account['type'],
                'debit_cents' => 0,
                'credit_cents' => 0,
            ]);

            if ($row['type'] === ChartAccount::TYPE_ASSET) {
                $bal = $row['debit_cents'] - $row['credit_cents'];
                if (! $showZero && $bal === 0) {
                    continue;
                }
                $assets[] = ['code' => $row['code'], 'name' => $row['name'], 'balance_cents' => $bal];
                $assetsTotal += $bal;
            }

            if ($row['type'] === ChartAccount::TYPE_LIABILITY) {
                $bal = $row['credit_cents'] - $row['debit_cents'];
                if (! $showZero && $bal === 0) {
                    continue;
                }
                $liabilities[] = ['code' => $row['code'], 'name' => $row['name'], 'balance_cents' => $bal];
                $liabilitiesTotal += $bal;
            }

            if ($row['type'] === ChartAccount::TYPE_EQUITY) {
                $bal = $row['credit_cents'] - $row['debit_cents'];
                if (! $showZero && $bal === 0) {
                    continue;
                }
                $equity[] = ['code' => $row['code'], 'name' => $row['name'], 'balance_cents' => $bal];
                $equityTotal += $bal;
            }
        }

        $sort = fn (array $a, array $b) => strcmp($a['code'], $b['code']);
        usort($assets, $sort);
        usort($liabilities, $sort);
        usort($equity, $sort);

        $pl = $this->profitAndLoss(Carbon::parse('1970-01-01'), $asOf);

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'retained_earnings_cents' => $pl['net_income_cents'],
            'assets_total_cents' => $assetsTotal,
            'liabilities_plus_equity_cents' => $liabilitiesTotal + $equityTotal + $pl['net_income_cents'],
        ];
    }

    /**
     * Simplified cash flow: net income (period) plus summary of cash-like asset accounts.
     *
     * @return array{net_income_cents: int, cash_accounts: list<array{name: string, code: string, opening_cents: int, closing_cents: int, change_cents: int}>}
     */
    public function cashFlow(CarbonInterface $from, CarbonInterface $to): array
    {
        $pl = $this->profitAndLoss($from, $to);

        $cashAccounts = ChartAccount::query()
            ->where('company_id', $this->companyId)
            ->where('type', ChartAccount::TYPE_ASSET)
            ->orderBy('code')
            ->get()
            ->filter(fn (ChartAccount $a) => $this->isCashLikeAccount($a));

        $cashRows = [];

        foreach ($cashAccounts as $account) {
            $opening = $this->accountBalanceCents($account->id, Carbon::parse('1970-01-01'), (clone $from)->subDay());
            $closing = $this->accountBalanceCents($account->id, Carbon::parse('1970-01-01'), $to);
            $change = $closing - $opening;

            if ($opening === 0 && $closing === 0) {
                continue;
            }

            $cashRows[] = [
                'code' => $account->code,
                'name' => $account->name,
                'opening_cents' => $opening,
                'closing_cents' => $closing,
                'change_cents' => $change,
            ];
        }

        return [
            'net_income_cents' => $pl['net_income_cents'],
            'cash_accounts' => $cashRows,
        ];
    }

    /**
     * @return array{account: array{id: int, code: string, name: string}|null, opening_balance_cents: int, lines: list<array{id: int, date: string, reference: ?string, memo: ?string, description: ?string, debit_cents: int, credit_cents: int, balance_cents: int}>}
     */
    public function generalLedger(int $accountId, CarbonInterface $from, CarbonInterface $to): array
    {
        $account = ChartAccount::query()
            ->where('company_id', $this->companyId)
            ->whereKey($accountId)
            ->first();

        if (! $account) {
            return [
                'account' => null,
                'opening_balance_cents' => 0,
                'lines' => [],
            ];
        }

        $opening = $this->accountBalanceCents($account->id, Carbon::parse('1970-01-01'), (clone $from)->subDay());

        $lines = JournalLine::query()
            ->with('journalEntry')
            ->where('chart_account_id', $account->id)
            ->whereHas('journalEntry', function (Builder $q) use ($from, $to) {
                $q->where('company_id', $this->companyId)
                    ->where('status', JournalEntry::STATUS_APPROVED)
                    ->whereDate('transaction_date', '>=', $from)
                    ->whereDate('transaction_date', '<=', $to);
            })
            ->get()
            ->sortBy(function (JournalLine $line) {
                $entry = $line->journalEntry;

                return sprintf(
                    '%s-%010d-%010d',
                    $entry->transaction_date->format('Y-m-d'),
                    $entry->id,
                    $line->id,
                );
            })
            ->values();

        $running = $opening;
        $out = [];

        foreach ($lines as $line) {
            $entry = $line->journalEntry;
            $running += (int) $line->debit_cents - (int) $line->credit_cents;

            $out[] = [
                'id' => $line->id,
                'date' => $entry->transaction_date->toDateString(),
                'reference' => $entry->reference,
                'memo' => $entry->memo,
                'description' => $line->description,
                'debit_cents' => (int) $line->debit_cents,
                'credit_cents' => (int) $line->credit_cents,
                'balance_cents' => $running,
            ];
        }

        return [
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
            ],
            'opening_balance_cents' => $opening,
            'lines' => $out,
        ];
    }

    /**
     * @return Collection<string, array{code: string, name: string, type: string, debit_cents: int, credit_cents: int}>
     */
    protected function aggregateByAccount(Collection $lines): Collection
    {
        return $lines->groupBy('chart_account_id')->map(function (Collection $group) {
            /** @var JournalLine $first */
            $first = $group->first();
            $account = $first->chartAccount;

            return [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'debit_cents' => (int) $group->sum('debit_cents'),
                'credit_cents' => (int) $group->sum('credit_cents'),
            ];
        });
    }

    /**
     * @return Collection<int, array{code: string, name: string, type: string}>
     */
    protected function approvedAccountsById(): Collection
    {
        return ChartAccount::query()
            ->where('company_id', $this->companyId)
            ->approvedForJournals()
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type'])
            ->mapWithKeys(fn (ChartAccount $a) => [
                (int) $a->id => [
                    'code' => $a->code,
                    'name' => $a->name,
                    'type' => $a->type,
                ],
            ]);
    }

    protected function accountBalanceCents(int $chartAccountId, CarbonInterface $from, CarbonInterface $to): int
    {
        $debit = (int) JournalLine::query()
            ->where('chart_account_id', $chartAccountId)
            ->whereHas('journalEntry', function (Builder $q) use ($from, $to) {
                $q->where('company_id', $this->companyId)
                    ->where('status', JournalEntry::STATUS_APPROVED);

                if ($to !== null) {
                    $q->whereDate('transaction_date', '<=', $to);
                }

                if ($from !== null) {
                    $q->whereDate('transaction_date', '>=', $from);
                }
            })
            ->sum('debit_cents');

        $credit = (int) JournalLine::query()
            ->where('chart_account_id', $chartAccountId)
            ->whereHas('journalEntry', function (Builder $q) use ($from, $to) {
                $q->where('company_id', $this->companyId)
                    ->where('status', JournalEntry::STATUS_APPROVED);

                if ($to !== null) {
                    $q->whereDate('transaction_date', '<=', $to);
                }

                if ($from !== null) {
                    $q->whereDate('transaction_date', '>=', $from);
                }
            })
            ->sum('credit_cents');

        return $debit - $credit;
    }

    protected function isCashLikeAccount(ChartAccount $a): bool
    {
        $n = strtolower($a->name);

        return str_contains($n, 'cash')
            || str_contains($n, 'bank')
            || str_contains($n, 'checking')
            || str_contains($n, 'savings');
    }
}
