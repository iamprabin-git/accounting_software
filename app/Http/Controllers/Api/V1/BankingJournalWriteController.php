<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Services\FinanceJournalPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class BankingJournalWriteController extends Controller
{
    public function storeTwoLine(Request $request, FinanceJournalPostingService $posting): JsonResponse
    {
        /** @var Company $company */
        $company = $request->attributes->get('banking_company');
        $user = $request->user();

        if (! $user->canCreateJournalEntries()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'memo' => ['required', 'string', 'max:2000'],
            'reference' => ['nullable', 'string', 'max:64'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'debit_chart_account_id' => ['required', 'integer'],
            'credit_chart_account_id' => ['required', 'integer', 'different:debit_chart_account_id'],
            'member_id' => [
                'nullable',
                'integer',
                Rule::exists('members', 'id')->where(
                    fn ($q) => $q->where('company_id', $company->id),
                ),
            ],
        ]);

        $cents = (int) round(((float) $validated['amount']) * 100);

        try {
            $entry = $posting->postTwoLineJournal(
                $company->id,
                $user,
                $validated['transaction_date'],
                $validated['memo'],
                $validated['reference'] ?? null,
                $cents,
                (int) $validated['debit_chart_account_id'],
                (int) $validated['credit_chart_account_id'],
                isset($validated['member_id']) ? (int) $validated['member_id'] : null,
                null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->journalPayload($entry),
        ], 201);
    }

    /**
     * Transfer: funds leave `from` (credited) and enter `to` (debited).
     */
    public function storeTransfer(Request $request, FinanceJournalPostingService $posting): JsonResponse
    {
        /** @var Company $company */
        $company = $request->attributes->get('banking_company');
        $user = $request->user();

        if (! $user->canCreateJournalEntries()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'memo' => ['required', 'string', 'max:2000'],
            'reference' => ['nullable', 'string', 'max:64'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'from_chart_account_id' => ['required', 'integer'],
            'to_chart_account_id' => ['required', 'integer', 'different:from_chart_account_id'],
            'member_id' => [
                'nullable',
                'integer',
                Rule::exists('members', 'id')->where(
                    fn ($q) => $q->where('company_id', $company->id),
                ),
            ],
        ]);

        $cents = (int) round(((float) $validated['amount']) * 100);
        $memo = $validated['memo'].' (API transfer)';

        try {
            $entry = $posting->postTwoLineJournal(
                $company->id,
                $user,
                $validated['transaction_date'],
                $memo,
                $validated['reference'] ?? null,
                $cents,
                (int) $validated['to_chart_account_id'],
                (int) $validated['from_chart_account_id'],
                isset($validated['member_id']) ? (int) $validated['member_id'] : null,
                null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->journalPayload($entry),
        ], 201);
    }

    private function journalPayload(JournalEntry $entry): array
    {
        $entry->loadMissing('lines');

        return [
            'id' => $entry->id,
            'status' => $entry->status,
            'posted_number' => $entry->posted_number,
            'transaction_date' => $entry->transaction_date?->toDateString(),
            'memo' => $entry->memo,
            'reference' => $entry->reference,
            'member_id' => $entry->member_id,
            'lines' => $entry->lines->map(fn ($l) => [
                'chart_account_id' => $l->chart_account_id,
                'debit_cents' => (int) $l->debit_cents,
                'credit_cents' => (int) $l->credit_cents,
            ])->all(),
        ];
    }
}
