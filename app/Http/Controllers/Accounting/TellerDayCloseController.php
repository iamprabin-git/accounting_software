<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Controller;
use App\Models\TellerDayClose;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TellerDayCloseController extends Controller
{
    use ResolvesAccountingCompany;

    public function create(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user && $user->canEditAccounting(), 403);

        $company = $this->accountingCompany($request);

        $recent = TellerDayClose::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->latest('close_date')
            ->limit(15)
            ->get()
            ->map(fn (TellerDayClose $c) => [
                'id' => $c->id,
                'close_date' => $c->close_date?->toDateString(),
                'opening_cash_cents' => (int) $c->opening_cash_cents,
                'counted_cash_cents' => (int) $c->counted_cash_cents,
                'expected_cash_cents' => $c->expected_cash_cents !== null ? (int) $c->expected_cash_cents : null,
                'variance_versus_opening_cents' => (int) $c->counted_cash_cents - (int) $c->opening_cash_cents,
            ]);

        return Inertia::render('Accounting/Teller/DayClose', [
            'recentCloses' => $recent,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
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

        $exists = TellerDayClose::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->whereDate('close_date', $validated['close_date'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'close_date' => __('You already recorded a day close for this date.'),
            ]);
        }

        TellerDayClose::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'close_date' => $validated['close_date'],
            'opening_cash_cents' => $opening,
            'counted_cash_cents' => $counted,
            'expected_cash_cents' => $expected,
            'memo' => $validated['memo'] ?? null,
        ]);

        return back()->with('status', __('Day close saved.'));
    }
}
