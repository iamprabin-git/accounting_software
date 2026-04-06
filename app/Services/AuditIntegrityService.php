<?php

namespace App\Services;

use App\Models\AccountingAuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuditIntegrityService
{
    /**
     * @return array{
     *   valid: bool,
     *   checked_count: int,
     *   first_broken_event_id: ?int,
     *   first_broken_reason: ?string
     * }
     */
    public function verifyCompany(int $companyId): array
    {
        $previousHash = null;
        $checked = 0;
        $firstBrokenId = null;
        $firstBrokenReason = null;
        $audit = app(AccountingAuditService::class);

        foreach (
            AccountingAuditLog::query()
                ->where('company_id', $companyId)
                ->orderBy('id')
                ->cursor() as $log
        ) {
            $checked++;

            $storedPrev = $log->previous_event_hash;
            if ($storedPrev !== $previousHash) {
                $firstBrokenId = (int) $log->id;
                $firstBrokenReason = 'previous_hash_mismatch';
                break;
            }

            $expected = $audit->computeEventHash(
                companyId: (int) $log->company_id,
                journalEntryId: $log->journal_entry_id ? (int) $log->journal_entry_id : null,
                userId: $log->user_id ? (int) $log->user_id : null,
                action: (string) $log->action,
                metadata: $log->metadata ?? [],
                createdAtIso: $log->created_at?->toIso8601String(),
                previousHash: $storedPrev,
            );

            if ($expected !== $log->event_hash) {
                $firstBrokenId = (int) $log->id;
                $firstBrokenReason = 'event_hash_mismatch';
                break;
            }

            $previousHash = $log->event_hash;
        }

        return [
            'valid' => $firstBrokenId === null,
            'checked_count' => $checked,
            'first_broken_event_id' => $firstBrokenId,
            'first_broken_reason' => $firstBrokenReason,
        ];
    }

    /**
     * @param  array{valid: bool, checked_count: int, first_broken_event_id: ?int, first_broken_reason: ?string}  $result
     */
    public function verificationSignature(int $companyId, string $mode, array $result, ?int $actorUserId): string
    {
        $payload = json_encode([
            'company_id' => $companyId,
            'mode' => $mode,
            'result' => $result,
            'actor_user_id' => $actorUserId,
            'signed_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', $payload ?: '', (string) config('app.key'));
    }

    /**
     * @param  array{valid: bool, checked_count: int, first_broken_event_id: ?int, first_broken_reason: ?string}  $result
     */
    public function notifyFailure(Company $company, array $result, string $mode): void
    {
        $emails = User::query()
            ->where(function ($q) use ($company) {
                $q->where('role', User::ROLE_ADMIN)
                    ->orWhere(function ($inner) use ($company) {
                        $inner->where('role', User::ROLE_COMPANY)
                            ->where('company_id', $company->id);
                    });
            })
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($emails === []) {
            return;
        }

        $subject = '[Alert] Audit integrity broken for company '.$company->id;
        $body = "Audit integrity check failed.\n\n"
            ."Company: {$company->name} (#{$company->id})\n"
            ."Mode: {$mode}\n"
            ."Checked events: {$result['checked_count']}\n"
            .'First broken event id: '.($result['first_broken_event_id'] ?? 'n/a')."\n"
            .'Reason: '.($result['first_broken_reason'] ?? 'n/a')."\n"
            .'At: '.now()->toIso8601String()."\n";

        try {
            foreach ($emails as $email) {
                Mail::raw($body, function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send audit integrity failure email', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
