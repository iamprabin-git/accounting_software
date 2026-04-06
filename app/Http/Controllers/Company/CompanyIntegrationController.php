<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\BankingWebhookSubscription;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class CompanyIntegrationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->ensureIntegrationsUser($request);
        $company = $this->integrationCompany($request, $user);
        abort_if($company === null, 403);

        $abilities = config('banking_api.token_abilities', []);
        $webhookEvents = config('banking_api.webhook_events', []);

        $tokens = $user->tokens()
            ->orderByDesc('id')
            ->get()
            ->map(fn (PersonalAccessToken $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'abilities' => $t->abilities ?? [],
                'last_used_at' => $t->last_used_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
            ]);

        $webhooks = BankingWebhookSubscription::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->get()
            ->map(fn (BankingWebhookSubscription $w) => [
                'id' => $w->id,
                'name' => $w->name,
                'url' => $w->url,
                'events' => $w->events ?? [],
                'is_active' => (bool) $w->is_active,
            ]);

        return Inertia::render('Company/Integrations/Index', [
            'tokenAbilities' => $abilities,
            'webhookEvents' => $webhookEvents,
            'tokens' => $tokens,
            'webhooks' => $webhooks,
            'integrationContext' => [
                'is_platform_admin' => $user->isAdmin(),
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],
            ],
            'flash' => [
                'new_token' => $request->session()->pull('integration_new_token'),
                'new_webhook_secret' => $request->session()->pull('integration_new_webhook_secret'),
            ],
        ]);
    }

    public function storeToken(Request $request): RedirectResponse
    {
        $user = $this->ensureIntegrationsUser($request);

        $allowed = array_keys(config('banking_api.token_abilities', []));
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', Rule::in($allowed)],
        ]);

        $scopes = array_values(array_intersect($validated['scopes'] ?? [], $allowed));
        if ($scopes === []) {
            $scopes = ['banking:read'];
        }

        if (! $user->canCreateJournalEntries()) {
            $scopes = array_values(array_diff($scopes, ['banking:journal']));
        }

        if (! $user->isCompany() && ! $user->isAdmin()) {
            $scopes = array_values(array_diff($scopes, ['banking:webhooks:manage']));
        }

        if ($scopes === []) {
            $scopes = ['banking:read'];
        }

        $plain = $user->createToken($validated['name'], $scopes)->plainTextToken;

        return redirect()
            ->route('company.integrations.index')
            ->with('integration_new_token', $plain);
    }

    public function destroyToken(Request $request, int $token): RedirectResponse
    {
        $user = $this->ensureIntegrationsUser($request);

        $user->tokens()->whereKey($token)->delete();

        return redirect()
            ->route('company.integrations.index')
            ->with('status', __('Token revoked.'));
    }

    public function storeWebhook(Request $request): RedirectResponse
    {
        $user = $this->ensureIntegrationsUser($request);
        $company = $this->integrationCompany($request, $user);
        abort_if($company === null, 403);

        $allowedEvents = array_keys(config('banking_api.webhook_events', []));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'string', 'url', 'max:2048'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', Rule::in($allowedEvents)],
        ]);

        $plainSecret = Str::random(40);

        BankingWebhookSubscription::query()->create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'url' => $validated['url'],
            'secret' => $plainSecret,
            'events' => $validated['events'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('company.integrations.index')
            ->with('integration_new_webhook_secret', $plainSecret);
    }

    public function destroyWebhook(Request $request, int $webhook): RedirectResponse
    {
        $user = $this->ensureIntegrationsUser($request);
        $company = $this->integrationCompany($request, $user);
        abort_if($company === null, 403);

        BankingWebhookSubscription::query()
            ->where('company_id', $company->id)
            ->whereKey($webhook)
            ->delete();

        return redirect()
            ->route('company.integrations.index')
            ->with('status', __('Webhook removed.'));
    }

    private function ensureIntegrationsUser(Request $request): User
    {
        $user = $request->user();
        abort_unless(
            $user && (($user->isCompany() && $user->company_id) || $user->isAdmin()),
            403,
        );

        return $user;
    }

    private function integrationCompany(Request $request, User $user): ?Company
    {
        if ($user->isCompany() && $user->company_id) {
            return Company::query()->find($user->company_id);
        }

        if ($user->isAdmin()) {
            return Company::resolvedForWebRequest($request);
        }

        return null;
    }
}
