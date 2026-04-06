<?php

namespace App\Jobs;

use App\Models\BankingWebhookSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliverBankingWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $subscriptionId,
        public array $payload,
    ) {}

    public function handle(): void
    {
        $sub = BankingWebhookSubscription::query()->find($this->subscriptionId);
        if ($sub === null || ! $sub->is_active) {
            return;
        }

        $body = json_encode($this->payload, JSON_THROW_ON_ERROR);
        $secret = $sub->secret;
        if ($secret === null || $secret === '') {
            Log::warning('banking_webhook.missing_secret', ['subscription_id' => $sub->id]);

            return;
        }

        $signature = hash_hmac('sha256', $body, $secret);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Banking-Event' => (string) ($this->payload['event'] ?? ''),
                    'X-Banking-Signature' => 'sha256='.$signature,
                ])
                ->withBody($body, 'application/json')
                ->post($sub->url);

            if (! $response->successful()) {
                Log::warning('banking_webhook.http_error', [
                    'subscription_id' => $sub->id,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('banking_webhook.exception', [
                'subscription_id' => $sub->id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
