<?php

namespace App\Listeners;

use App\Events\JournalEntryPosted;
use App\Jobs\DeliverBankingWebhookJob;
use App\Models\BankingWebhookSubscription;

class QueueBankingWebhookDeliveries
{
    public function handle(JournalEntryPosted $event): void
    {
        $journal = $event->journal->loadMissing('lines');

        BankingWebhookSubscription::query()
            ->where('company_id', $journal->company_id)
            ->where('is_active', true)
            ->get()
            ->each(function (BankingWebhookSubscription $sub) use ($journal) {
                if (! $sub->wantsEvent('journal.posted')) {
                    return;
                }

                $amountCents = (int) $journal->lines->sum('debit_cents');

                DeliverBankingWebhookJob::dispatch(
                    $sub->id,
                    [
                        'event' => 'journal.posted',
                        'company_id' => $journal->company_id,
                        'occurred_at' => now()->toIso8601String(),
                        'data' => [
                            'journal_entry_id' => $journal->id,
                            'posted_number' => $journal->posted_number,
                            'status' => $journal->status,
                            'transaction_date' => $journal->transaction_date?->toDateString(),
                            'memo' => $journal->memo,
                            'reference' => $journal->reference,
                            'member_id' => $journal->member_id,
                            'amount_cents' => $amountCents,
                        ],
                    ],
                );
            });
    }
}
