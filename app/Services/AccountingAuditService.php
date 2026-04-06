<?php

namespace App\Services;

use App\Models\AccountingAuditLog;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Http\Request;

class AccountingAuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logJournalAction(
        int $companyId,
        ?int $journalEntryId,
        string $action,
        ?User $actor = null,
        array $metadata = [],
        ?Request $request = null,
    ): void {
        $createdAt = now();
        $previousHash = AccountingAuditLog::query()
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->value('event_hash');

        $eventHash = $this->computeEventHash(
            companyId: $companyId,
            journalEntryId: $journalEntryId,
            userId: $actor?->id,
            action: $action,
            metadata: $metadata,
            createdAtIso: $createdAt->toIso8601String(),
            previousHash: $previousHash,
        );

        AccountingAuditLog::query()->create([
            'company_id' => $companyId,
            'user_id' => $actor?->id,
            'actor_ip' => $request?->ip(),
            'actor_user_agent' => $request?->userAgent(),
            'journal_entry_id' => $journalEntryId,
            'action' => $action,
            'metadata' => $metadata === [] ? null : $metadata,
            'previous_event_hash' => $previousHash,
            'event_hash' => $eventHash,
            'created_at' => $createdAt,
        ]);
    }

    public function logForJournal(
        JournalEntry $journalEntry,
        string $action,
        ?User $actor = null,
        array $metadata = [],
        ?Request $request = null,
    ): void {
        $this->logJournalAction(
            (int) $journalEntry->company_id,
            (int) $journalEntry->id,
            $action,
            $actor,
            $metadata,
            $request,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function computeEventHash(
        int $companyId,
        ?int $journalEntryId,
        ?int $userId,
        string $action,
        array $metadata,
        ?string $createdAtIso,
        ?string $previousHash,
    ): string {
        $payload = json_encode([
            'company_id' => $companyId,
            'journal_entry_id' => $journalEntryId,
            'user_id' => $userId,
            'action' => $action,
            'metadata' => $metadata,
            'created_at' => $createdAtIso,
            'previous_event_hash' => $previousHash,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', $payload ?: '', (string) config('app.key'));
    }
}
