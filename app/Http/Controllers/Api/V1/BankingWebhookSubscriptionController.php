<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BankingWebhookSubscription;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BankingWebhookSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Company $company */
        $company = $request->attributes->get('banking_company');

        $rows = BankingWebhookSubscription::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->get()
            ->map(fn (BankingWebhookSubscription $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'url' => $s->url,
                'events' => $s->events ?? [],
                'is_active' => (bool) $s->is_active,
            ]);

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Company $company */
        $company = $request->attributes->get('banking_company');

        $allowedEvents = array_keys(config('banking_api.webhook_events', []));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'string', 'url', 'max:2048'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', Rule::in($allowedEvents)],
        ]);

        $plainSecret = Str::random(40);

        $sub = BankingWebhookSubscription::query()->create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'url' => $validated['url'],
            'secret' => $plainSecret,
            'events' => $validated['events'],
            'is_active' => true,
        ]);

        return response()->json([
            'data' => [
                'id' => $sub->id,
                'name' => $sub->name,
                'url' => $sub->url,
                'events' => $sub->events,
                'secret' => $plainSecret,
                'message' => 'Store this secret securely; it will not be shown again.',
            ],
        ], 201);
    }

    public function destroy(Request $request, int $webhook): JsonResponse
    {
        /** @var Company $company */
        $company = $request->attributes->get('banking_company');

        $deleted = BankingWebhookSubscription::query()
            ->where('company_id', $company->id)
            ->whereKey($webhook)
            ->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json(['message' => 'Webhook removed.']);
    }
}
