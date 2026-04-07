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
        ];
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
