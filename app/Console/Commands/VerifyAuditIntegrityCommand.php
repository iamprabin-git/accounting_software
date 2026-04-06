<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\AccountingAuditService;
use App\Services\AuditIntegrityService;
use Illuminate\Console\Command;

class VerifyAuditIntegrityCommand extends Command
{
    protected $signature = 'audits:verify-integrity {--company_id=} {--notify}';

    protected $description = 'Verify accounting audit hash-chain integrity';

    public function handle(): int
    {
        $companyId = $this->option('company_id');
        $notify = (bool) $this->option('notify');

        $query = Company::query();
        if ($companyId) {
            $query->whereKey((int) $companyId);
        }

        $companies = $query->orderBy('id')->get();
        if ($companies->isEmpty()) {
            $this->info('No companies found.');

            return self::SUCCESS;
        }

        $integrityService = app(AuditIntegrityService::class);
        $auditService = app(AccountingAuditService::class);

        $failed = 0;
        foreach ($companies as $company) {
            $result = $integrityService->verifyCompany($company->id);
            $signature = $integrityService->verificationSignature(
                companyId: $company->id,
                mode: 'nightly',
                result: $result,
                actorUserId: null,
            );

            $auditService->logJournalAction(
                companyId: $company->id,
                journalEntryId: null,
                action: $result['valid'] ? 'audit.integrity_nightly_ok' : 'audit.integrity_nightly_failed',
                actor: null,
                metadata: [
                    ...$result,
                    'mode' => 'nightly',
                    'signature' => $signature,
                ],
                request: null,
            );

            if ($result['valid']) {
                $this->info("Company {$company->id}: OK");

                continue;
            }

            $failed++;
            $this->error("Company {$company->id}: FAILED (event {$result['first_broken_event_id']})");
            if ($notify) {
                $integrityService->notifyFailure($company, $result, 'nightly');
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
