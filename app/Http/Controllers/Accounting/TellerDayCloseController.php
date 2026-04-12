<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Controller;
use App\Models\TellerDayClose;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TellerDayCloseController extends Controller
{
    use ResolvesAccountingCompany;

    /**
     * @return array<string, string|int>
     */
    private function tellerCreateRedirectParams(Request $request, int $companyId, string $date): array
    {
        $query = ['date' => $date];
        if ($request->user()?->isAdmin()) {
            $query['company_id'] = $companyId;
        }

        return $query;
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user && $user->canEditAccounting(), 403);

        $company = $this->accountingCompany($request);

        $selectedDate = (string) ($request->input('date') ?: Carbon::today()->toDateString());

        $recent = TellerDayClose::query()
            ->where('company_id', $company->id)
            ->latest('close_date')
            ->limit(15)
            ->get()
            ->map(fn (TellerDayClose $c) => [
                'id' => $c->id,
                'close_date' => $c->close_date?->toDateString(),
                'day_status' => $c->day_status ?? TellerDayClose::STATUS_CLOSED,
                'started_at' => $c->started_at?->toIso8601String(),
                'ended_at' => $c->ended_at?->toIso8601String(),
                'opening_cash_cents' => (int) $c->opening_cash_cents,
                'counted_cash_cents' => (int) $c->counted_cash_cents,
                'expected_cash_cents' => $c->expected_cash_cents !== null ? (int) $c->expected_cash_cents : null,
                'vault_opening_cash_cents' => $c->vault_opening_cash_cents !== null ? (int) $c->vault_opening_cash_cents : null,
                'vault_returned_cash_cents' => $c->vault_returned_cash_cents !== null ? (int) $c->vault_returned_cash_cents : null,
                'system_cash_cents' => $c->system_cash_cents !== null ? (int) $c->system_cash_cents : null,
                'closing_error_cents' => (int) ($c->closing_error_cents ?? 0),
                'variance_versus_opening_cents' => (int) $c->counted_cash_cents - (int) $c->opening_cash_cents,
            ]);

        $openDay = TellerDayClose::query()
            ->where('company_id', $company->id)
            ->whereDate('close_date', $selectedDate)
            ->where('day_status', TellerDayClose::STATUS_OPEN)
            ->first();

        return Inertia::render('Accounting/Teller/DayClose', [
            'recentCloses' => $recent,
            'selectedDate' => $selectedDate,
            'openDay' => $openDay ? [
                'id' => $openDay->id,
                'close_date' => $openDay->close_date?->toDateString(),
                'vault_opening_cash_cents' => (int) ($openDay->vault_opening_cash_cents ?? 0),
                'cash_received_cents' => (int) ($openDay->cash_received_cents ?? 0),
                'started_at' => $openDay->started_at?->toIso8601String(),
            ] : null,
            'reportLinks' => [
                'trial_balance' => route('reports.trial-balance', ['as_of' => $selectedDate]),
                'profit_loss' => route('reports.profit-loss', ['from' => $selectedDate, 'to' => $selectedDate]),
                'balance_sheet' => route('reports.balance-sheet', ['as_of' => $selectedDate]),
                'cash_flow' => route('reports.cash-flow', ['from' => $selectedDate, 'to' => $selectedDate]),
            ],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->canEditAccounting(), 403);

        if ($user->isAdmin()) {
            $request->validate([
                'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            ]);
            $request->session()->put('accounting_company_id', (int) $request->input('company_id'));
        }

        $company = $this->accountingCompany($request);
        $validated = $request->validate([
            'close_date' => ['required', 'date'],
            'vault_opening_cash' => ['required', 'numeric', 'min:0'],
            'memo' => ['nullable', 'string', 'max:500'],
        ]);

        $exists = TellerDayClose::query()
            ->where('company_id', $company->id)
            ->whereDate('close_date', $validated['close_date'])
            ->exists();

        $date = (string) $validated['close_date'];

        if ($exists) {
            return redirect()
                ->route(
                    'teller.day-close.create',
                    $this->tellerCreateRedirectParams($request, $company->id, $date),
                )
                ->withErrors([
                    'close_date' => __('Day already started/closed for this date.'),
                ]);
        }

        $opening = (int) round(((float) $validated['vault_opening_cash']) * 100);

        TellerDayClose::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'close_date' => $date,
            'day_status' => TellerDayClose::STATUS_OPEN,
            'opening_cash_cents' => $opening,
            'vault_opening_cash_cents' => $opening,
            'counted_cash_cents' => 0,
            'cash_received_cents' => 0,
            'closing_error_cents' => 0,
            'memo' => $validated['memo'] ?? null,
            'started_at' => now(),
        ]);

        return redirect()
            ->route(
                'teller.day-close.create',
                $this->tellerCreateRedirectParams($request, $company->id, $date),
            )
            ->with('status', __('Day started. Cash transactions are now enabled.'));
    }

    public function end(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->canEditAccounting(), 403);

        if ($user->isAdmin()) {
            $request->validate([
                'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            ]);
            $request->session()->put('accounting_company_id', (int) $request->input('company_id'));
        }

        $company = $this->accountingCompany($request);
        $validated = $request->validate([
            'close_date' => ['required', 'date'],
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'system_cash' => ['required', 'numeric', 'min:0'],
            'vault_returned_cash' => ['required', 'numeric', 'min:0'],
            'memo' => ['nullable', 'string', 'max:500'],
        ]);

        $open = TellerDayClose::query()
            ->where('company_id', $company->id)
            ->whereDate('close_date', $validated['close_date'])
            ->where('day_status', TellerDayClose::STATUS_OPEN)
            ->first();

        $date = (string) $validated['close_date'];

        if (! $open) {
            return redirect()
                ->route(
                    'teller.day-close.create',
                    $this->tellerCreateRedirectParams($request, $company->id, $date),
                )
                ->withErrors([
                    'close_date' => __('No open day found for this date.'),
                ]);
        }

        $counted = (int) round(((float) $validated['counted_cash']) * 100);
        $system = (int) round(((float) $validated['system_cash']) * 100);
        $returned = (int) round(((float) $validated['vault_returned_cash']) * 100);
        $error = $counted - $system;

        if ($counted !== $returned) {
            return redirect()
                ->route(
                    'teller.day-close.create',
                    $this->tellerCreateRedirectParams($request, $company->id, $date),
                )
                ->withErrors([
                    'vault_returned_cash' => __('Returned amount must equal counted cash.'),
                ]);
        }

        if ($error !== 0) {
            return redirect()
                ->route(
                    'teller.day-close.create',
                    $this->tellerCreateRedirectParams($request, $company->id, $date),
                )
                ->withErrors([
                    'system_cash' => __('End of day can only complete when cash error is zero (counted equals system cash).'),
                ]);
        }

        $open->update([
            'day_status' => TellerDayClose::STATUS_CLOSED,
            'counted_cash_cents' => $counted,
            'expected_cash_cents' => $system,
            'system_cash_cents' => $system,
            'vault_returned_cash_cents' => $returned,
            'closing_error_cents' => 0,
            'memo' => $validated['memo'] ?? $open->memo,
            'ended_at' => now(),
        ]);

        return redirect()
            ->route(
                'teller.day-close.create',
                $this->tellerCreateRedirectParams($request, $company->id, $date),
            )
            ->with('status', __('Day ended successfully. Vault returned and variance is zero.'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->canEditAccounting(), 403);

        if ($user->isAdmin()) {
            $request->validate([
                'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            ]);
            $request->session()->put('accounting_company_id', (int) $request->input('company_id'));
        }

        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'close_date' => ['required', 'date'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'expected_cash' => ['nullable', 'numeric', 'min:0'],
            'memo' => ['nullable', 'string', 'max:500'],
        ]);

        $opening = (int) round(((float) $validated['opening_cash']) * 100);
        $counted = (int) round(((float) $validated['counted_cash']) * 100);
        $expected = isset($validated['expected_cash'])
            ? (int) round(((float) $validated['expected_cash']) * 100)
            : null;

        $date = (string) $validated['close_date'];

        $exists = TellerDayClose::query()
            ->where('company_id', $company->id)
            ->whereDate('close_date', $date)
            ->exists();

        if ($exists) {
            return redirect()
                ->route(
                    'teller.day-close.create',
                    $this->tellerCreateRedirectParams($request, $company->id, $date),
                )
                ->withErrors([
                    'close_date' => __('You already recorded a day close for this date.'),
                ]);
        }

        TellerDayClose::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'close_date' => $date,
            'day_status' => TellerDayClose::STATUS_CLOSED,
            'opening_cash_cents' => $opening,
            'vault_opening_cash_cents' => $opening,
            'counted_cash_cents' => $counted,
            'expected_cash_cents' => $expected,
            'system_cash_cents' => $expected,
            'vault_returned_cash_cents' => $counted,
            'closing_error_cents' => $expected !== null ? $counted - $expected : 0,
            'memo' => $validated['memo'] ?? null,
            'started_at' => now(),
            'ended_at' => now(),
        ]);

        return redirect()
            ->route(
                'teller.day-close.create',
                $this->tellerCreateRedirectParams($request, $company->id, $date),
            )
            ->with('status', __('Day close saved.'));
    }
}
