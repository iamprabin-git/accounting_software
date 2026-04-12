<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\ResolvesCompanyForCompanyWebRoutes;
use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Services\CompanyPortableDatabaseExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class CompanyConfigurationController extends Controller
{
    use ResolvesCompanyForCompanyWebRoutes;

    public function edit(Request $request): Response
    {
        $company = $this->companyForCompanyWebRoutes($request);
        $company->loadMissing(['journalLockUpdatedBy:id,name']);

        $inventoryAccounts = ChartAccount::query()
            ->where('company_id', $company->id)
            ->approvedForJournals()
            ->where('type', ChartAccount::TYPE_ASSET)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (ChartAccount $a) => [
                'id' => $a->id,
                'label' => $a->code.' — '.$a->name,
            ])
            ->all();

        $liabilityAccounts = ChartAccount::query()
            ->where('company_id', $company->id)
            ->approvedForJournals()
            ->where('type', ChartAccount::TYPE_LIABILITY)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (ChartAccount $a) => [
                'id' => $a->id,
                'label' => $a->code.' — '.$a->name,
            ])
            ->all();

        return Inertia::render('Company/Configuration/Index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'plan' => $company->plan,
                'feature_flags' => $company->featureFlagsForFrontend(),
                'journal_lock_date' => $company->journal_lock_date?->toDateString(),
                'journal_lock_reason' => $company->journal_lock_reason,
                'journal_lock_updated_at' => $company->journal_lock_updated_at?->toIso8601String(),
                'journal_lock_updated_by_name' => $company->journalLockUpdatedBy?->name,
                'next_journal_posted_number' => (int) $company->next_journal_posted_number,
                'dual_approval_threshold' => $company->dual_approval_threshold_cents !== null
                    ? round(((int) $company->dual_approval_threshold_cents) / 100, 2)
                    : null,
                'inventory_chart_account_id' => $company->inventory_chart_account_id,
                'backup' => $company->normalizedBackupConfiguration(),
                'cbs' => $company->normalizedCbsConfiguration(),
            ],
            'inventoryAccountOptions' => $inventoryAccounts,
            'liabilityAccountOptions' => $liabilityAccounts,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canManageCompanyWebSettings(), 403);

        $company = $this->companyForCompanyWebRoutes($request);

        $validated = $request->validate([
            'cbs_internal_notes' => ['nullable', 'string', 'max:10000'],
            'dual_approval_threshold' => ['nullable', 'numeric', 'min:0'],
            'inventory_chart_account_id' => [
                'nullable',
                'integer',
                Rule::exists('chart_accounts', 'id')->where(
                    fn ($q) => $q->where('company_id', $company->id)
                        ->where('approval_status', ChartAccount::STATUS_APPROVED)
                        ->where('type', ChartAccount::TYPE_ASSET)
                ),
            ],
            'backup_snapshots_root_folder' => ['nullable', 'string', 'max:500'],
            'backup_restore_instructions' => ['nullable', 'string', 'max:10000'],
            'backup_recorded_snapshots' => ['nullable', 'array', 'max:100'],
            'backup_recorded_snapshots.*.snapshot_date' => ['nullable', 'date'],
            'backup_recorded_snapshots.*.label' => ['nullable', 'string', 'max:160'],
            'backup_recorded_snapshots.*.path_or_filename' => ['nullable', 'string', 'max:500'],
            'deposit_interest_withholding_tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'deposit_interest_tax_payable_chart_account_id' => [
                'nullable',
                'integer',
                Rule::exists('chart_accounts', 'id')->where(
                    fn ($q) => $q->where('company_id', $company->id)
                        ->where('approval_status', ChartAccount::STATUS_APPROVED)
                        ->where('type', ChartAccount::TYPE_LIABILITY)
                ),
            ],
        ]);

        $recorded = [];
        foreach ($validated['backup_recorded_snapshots'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (
                empty($row['snapshot_date'])
                && empty($row['label'])
                && empty($row['path_or_filename'])
            ) {
                continue;
            }
            $recorded[] = [
                'snapshot_date' => $row['snapshot_date'] ?? null,
                'label' => $row['label'] ?? null,
                'path_or_filename' => $row['path_or_filename'] ?? null,
            ];
        }

        $cbsBefore = $company->normalizedCbsConfiguration();

        if ($request->exists('deposit_interest_withholding_tax_percent')) {
            $rawPct = $request->input('deposit_interest_withholding_tax_percent');
            $taxPercent = ($rawPct !== null && $rawPct !== '')
                ? min(100.0, max(0.0, (float) $rawPct))
                : 0.0;
        } else {
            $taxPercent = (float) $cbsBefore['deposit_interest_withholding_tax_percent'];
        }

        $taxPayableAccountId = null;
        if ($taxPercent > 0) {
            if ($request->filled('deposit_interest_tax_payable_chart_account_id')) {
                $taxPayableAccountId = (int) $request->input('deposit_interest_tax_payable_chart_account_id');
            } else {
                $taxPayableAccountId = $cbsBefore['deposit_interest_tax_payable_chart_account_id'];
            }
            if ($taxPayableAccountId === null || $taxPayableAccountId <= 0) {
                throw ValidationException::withMessages([
                    'deposit_interest_tax_payable_chart_account_id' => __('Select a liability account for tax withheld on savings interest when the rate is above zero.'),
                ]);
            }
        }

        $company->fill([
            'cbs_configuration' => [
                'enforce_holiday_blackout' => $request->has('enforce_holiday_blackout')
                    ? $request->boolean('enforce_holiday_blackout')
                    : $company->cbsHolidayBlackoutEnabled(),
                'internal_notes' => $validated['cbs_internal_notes'] ?? '',
                'deposit_interest_withholding_tax_percent' => $taxPercent,
                'deposit_interest_tax_payable_chart_account_id' => $taxPayableAccountId,
            ],
            'dual_approval_threshold_cents' => isset($validated['dual_approval_threshold']) && $validated['dual_approval_threshold'] !== null && $validated['dual_approval_threshold'] !== ''
                ? (int) round(((float) $validated['dual_approval_threshold']) * 100)
                : null,
            'inventory_chart_account_id' => $validated['inventory_chart_account_id'] ?? null,
            'backup_configuration' => [
                'snapshots_root_folder' => $validated['backup_snapshots_root_folder'] ?? '',
                'restore_instructions' => $validated['backup_restore_instructions'] ?? '',
                'recorded_snapshots' => $recorded,
            ],
        ]);

        $company->save();

        return redirect()
            ->route(
                'company.configuration.edit',
                $this->companyIdQueryForRedirect($request, $company),
            )
            ->with('status', __('Organization configuration saved.'));
    }

    public function downloadPortableBackupZip(
        Request $request,
        CompanyPortableDatabaseExportService $exporter,
    ): BinaryFileResponse {
        abort_unless($request->user()?->canManageCompanyWebSettings(), 403);

        $company = $this->companyForCompanyWebRoutes($request);
        $exporter->writeDailySnapshot($company);

        $dir = $exporter->directoryForCompany($company->id);
        $files = array_values(array_filter(
            glob($dir.DIRECTORY_SEPARATOR.'*') ?: [],
            static fn (string $p): bool => is_file($p)
                && (str_ends_with($p, '.sqlite') || str_ends_with($p, '.json')),
        ));

        if ($files === []) {
            abort(500, __('No portable backup files were found after export.'));
        }

        if (! class_exists(ZipArchive::class)) {
            $sqliteFiles = array_values(array_filter(
                $files,
                static fn (string $p): bool => str_ends_with($p, '.sqlite'),
            ));
            $pick = $sqliteFiles !== [] ? end($sqliteFiles) : $files[0];
            $downloadBase = 'company-'.$company->id.'-backup-'.now()->format('Y-m-d-His');

            return response()->download(
                $pick,
                $downloadBase.(str_ends_with($pick, '.sqlite') ? '.sqlite' : '.bin'),
            );
        }

        $tmp = tempnam(sys_get_temp_dir(), 'co-bak-');
        if ($tmp === false) {
            abort(500, __('Could not create a temporary file for the backup archive.'));
        }
        unlink($tmp);
        $zipPath = $tmp.'.zip';

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, __('Could not create ZIP archive.'));
        }

        foreach ($files as $path) {
            $zip->addFile($path, basename($path));
        }
        $zip->close();

        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $company->name) ?: 'company';
        $safe = trim(substr($safe, 0, 60), '-');
        $downloadName = 'company-'.$company->id.'-'.$safe.'-backup-'.now()->format('Y-m-d-His').'.zip';

        return response()->download($zipPath, $downloadName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
