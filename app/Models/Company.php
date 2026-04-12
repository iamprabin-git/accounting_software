<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    public const PLAN_STARTER = 'starter';

    public const PLAN_PROFESSIONAL = 'professional';

    public const PLAN_ENTERPRISE = 'enterprise';

    protected $fillable = [
        'name',
        'plan',
        'feature_inventory_enabled',
        'feature_members_enabled',
        'address',
        'phone',
        'contact_email',
        'bank_payment_details',
        'payment_qr_path',
        'portal_show_payment_details',
        'logo_path',
        'inventory_chart_account_id',
        'journal_lock_date',
        'journal_lock_reason',
        'last_period_close_type',
        'journal_lock_updated_by_user_id',
        'journal_lock_updated_at',
        'next_journal_posted_number',
        'dual_approval_threshold_cents',
        'backup_configuration',
        'cbs_configuration',
    ];

    protected function casts(): array
    {
        return [
            'portal_show_payment_details' => 'boolean',
            'feature_inventory_enabled' => 'boolean',
            'feature_members_enabled' => 'boolean',
            'journal_lock_date' => 'date',
            'journal_lock_updated_at' => 'datetime',
            'dual_approval_threshold_cents' => 'integer',
            'backup_configuration' => 'array',
            'cbs_configuration' => 'array',
        ];
    }

    /**
     * @return array{
     *     enforce_holiday_blackout: bool,
     *     internal_notes: string,
     *     deposit_interest_withholding_tax_percent: float,
     *     deposit_interest_tax_payable_chart_account_id: int|null
     * }
     */
    public function normalizedCbsConfiguration(): array
    {
        $raw = $this->cbs_configuration ?? [];
        if (! is_array($raw)) {
            $raw = [];
        }

        $taxPercentRaw = $raw['deposit_interest_withholding_tax_percent'] ?? null;
        $taxPercent = 0.0;
        if ($taxPercentRaw !== null && $taxPercentRaw !== '') {
            $taxPercent = min(100.0, max(0.0, (float) $taxPercentRaw));
        }

        $taxAcct = $raw['deposit_interest_tax_payable_chart_account_id'] ?? null;
        $taxAcctId = $taxAcct !== null && $taxAcct !== ''
            ? (int) $taxAcct
            : null;

        return [
            'enforce_holiday_blackout' => array_key_exists('enforce_holiday_blackout', $raw)
                ? (bool) $raw['enforce_holiday_blackout']
                : true,
            'internal_notes' => (string) ($raw['internal_notes'] ?? ''),
            'deposit_interest_withholding_tax_percent' => $taxPercent,
            'deposit_interest_tax_payable_chart_account_id' => $taxAcctId,
        ];
    }

    public function cbsHolidayBlackoutEnabled(): bool
    {
        return $this->normalizedCbsConfiguration()['enforce_holiday_blackout'];
    }

    /**
     * Weekdays are working unless marked as a company holiday.
     * Weekends are non-working unless explicitly overridden on the calendar.
     */
    public function isWorkingDay(string $date): bool
    {
        if (CompanyHoliday::query()
            ->where('company_id', $this->id)
            ->whereDate('holiday_date', $date)
            ->exists()) {
            return false;
        }

        $d = Carbon::parse($date)->startOfDay();

        if ($d->isWeekend()) {
            return CompanyWorkingDayOverride::query()
                ->where('company_id', $this->id)
                ->whereDate('work_date', $date)
                ->exists();
        }

        return true;
    }

    public static function isWorkingTransactionDate(int $companyId, string $date): bool
    {
        $company = static::query()->find($companyId);

        return $company !== null && $company->isWorkingDay($date);
    }

    public function isJournalDateLocked(string|CarbonInterface $date): bool
    {
        if (! $this->journal_lock_date) {
            return false;
        }

        $txnDate = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        return $txnDate->toDateString() <= $this->journal_lock_date->toDateString();
    }

    /**
     * @return array<string, string>
     */
    public static function planLabels(): array
    {
        return [
            self::PLAN_STARTER => 'Starter — chart of accounts, journals & financial reports',
            self::PLAN_PROFESSIONAL => 'Professional — through inventory management',
            self::PLAN_ENTERPRISE => 'Enterprise — full suite (inventory & members can be toggled below)',
        ];
    }

    /**
     * Resolve which company’s plan applies for the current web request (customer app).
     */
    public static function resolvedForWebRequest(Request $request): ?self
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        if ($user->isEndUser()) {
            if (! $user->company_id) {
                return null;
            }

            return static::query()->find($user->company_id);
        }

        if ($user->isAdmin()) {
            $companyId = $request->integer('company_id') ?: (int) $request->session()->get('accounting_company_id');

            if (! $companyId) {
                $companyId = (int) (static::query()->orderBy('id')->value('id') ?? 0);
            }

            if (! $companyId) {
                return null;
            }

            $request->session()->put('accounting_company_id', $companyId);

            return static::query()->find($companyId);
        }

        if (! $user->company_id) {
            return null;
        }

        return static::query()->find($user->company_id);
    }

    public function allowsInventory(): bool
    {
        return match ($this->plan) {
            self::PLAN_STARTER => false,
            self::PLAN_PROFESSIONAL => true,
            self::PLAN_ENTERPRISE => (bool) $this->feature_inventory_enabled,
            default => false,
        };
    }

    public function allowsMembersModule(): bool
    {
        return $this->plan === self::PLAN_ENTERPRISE && (bool) $this->feature_members_enabled;
    }

    public function allowsDebtorsCreditors(): bool
    {
        return $this->plan === self::PLAN_ENTERPRISE;
    }

    public function allowsFinanceSuite(): bool
    {
        return $this->plan === self::PLAN_ENTERPRISE;
    }

    public function allowsCrm(): bool
    {
        return match ($this->plan) {
            self::PLAN_STARTER => false,
            self::PLAN_PROFESSIONAL, self::PLAN_ENTERPRISE => true,
            default => false,
        };
    }

    /**
     * @return array{plan: string, inventory: bool, members: bool, debtors_creditors: bool, finance: bool, crm: bool}
     */
    public function featureFlagsForFrontend(): array
    {
        return [
            'plan' => $this->plan,
            'inventory' => $this->allowsInventory(),
            'members' => $this->allowsMembersModule(),
            'debtors_creditors' => $this->allowsDebtorsCreditors(),
            'finance' => $this->allowsFinanceSuite(),
            'crm' => $this->allowsCrm(),
            /** Unified hub: members + finance (Enterprise with members on). */
            'core_banking_professional' => $this->allowsFinanceSuite() && $this->allowsMembersModule(),
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function chartAccounts(): HasMany
    {
        return $this->hasMany(ChartAccount::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function debtors(): HasMany
    {
        return $this->hasMany(Debtor::class);
    }

    public function creditors(): HasMany
    {
        return $this->hasMany(Creditor::class);
    }

    public function financialPositions(): HasMany
    {
        return $this->hasMany(FinancialPosition::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(CompanyHoliday::class);
    }

    public function workingDayOverrides(): HasMany
    {
        return $this->hasMany(CompanyWorkingDayOverride::class);
    }

    /**
     * @return array{suggested_root: string, snapshots_root_folder: string, restore_instructions: string, recorded_snapshots: list<array{snapshot_date?: string, label?: string, path_or_filename?: string}>}
     */
    public function normalizedBackupConfiguration(): array
    {
        $raw = $this->backup_configuration ?? [];
        if (! is_array($raw)) {
            $raw = [];
        }

        $recorded = $raw['recorded_snapshots'] ?? [];
        if (! is_array($recorded)) {
            $recorded = [];
        }

        $cleanRecorded = [];
        foreach ($recorded as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cleanRecorded[] = [
                'snapshot_date' => isset($row['snapshot_date']) ? (string) $row['snapshot_date'] : '',
                'label' => isset($row['label']) ? (string) $row['label'] : '',
                'path_or_filename' => isset($row['path_or_filename']) ? (string) $row['path_or_filename'] : '',
            ];
        }

        $root = trim((string) ($raw['snapshots_root_folder'] ?? ''));

        return [
            'suggested_root' => 'storage/app/company-backups/'.$this->id,
            'snapshots_root_folder' => $root,
            'restore_instructions' => (string) ($raw['restore_instructions'] ?? ''),
            'recorded_snapshots' => $cleanRecorded,
        ];
    }

    public function inventoryChartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'inventory_chart_account_id');
    }

    public function journalLockUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'journal_lock_updated_by_user_id');
    }

    /**
     * Root-relative URL so the logo works with the current browser host and avoids
     * APP_URL mismatches. Requires `public/storage` → `storage/app/public` (php artisan storage:link).
     */
    public function logoPublicUrl(): ?string
    {
        return $this->publicStorageUrl($this->attributes['logo_path'] ?? null);
    }

    public function paymentQrPublicUrl(): ?string
    {
        return $this->publicStorageUrl($this->attributes['payment_qr_path'] ?? null);
    }

    private function publicStorageUrl(mixed $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (is_array($path)) {
            $path = $path[0] ?? null;
            if ($path === null || $path === '') {
                return null;
            }
        }

        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        if (str_starts_with($path, '[')) {
            $decoded = json_decode($path, true);
            if (is_array($decoded)) {
                $path = $decoded[0] ?? '';
                $path = trim((string) $path);
            }
        }

        if ($path === '') {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        $url = '/storage/'.$path;

        if ($this->updated_at) {
            $url .= '?v='.$this->updated_at->getTimestamp();
        }

        return $url;
    }
}
