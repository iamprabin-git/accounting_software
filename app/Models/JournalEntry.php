<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JournalEntry extends Model
{
    use BelongsToCompany;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }

    public const FINANCE_CATEGORY_LOAN = 'loan';

    public const FINANCE_CATEGORY_SAVINGS = 'savings';

    public const FINANCE_CATEGORY_INVESTMENT = 'investment';

    protected $fillable = [
        'company_id',
        'member_id',
        'finance_category',
        'user_id',
        'reference',
        'memo',
        'transaction_date',
        'status',
        'submitted_at',
        'approved_by_user_id',
        'first_approved_by_user_id',
        'first_approved_at',
        'approved_at',
        'posted_number',
        'reversal_of_journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'submitted_at' => 'datetime',
            'first_approved_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function firstApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_approved_by_user_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_journal_entry_id');
    }

    public function reversalEntry(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_journal_entry_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function approvalComments(): HasMany
    {
        return $this->hasMany(JournalApprovalComment::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPosted(): bool
    {
        return $this->isApproved() && $this->posted_number !== null;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isBalanced(): bool
    {
        $this->loadMissing('lines');

        $debits = (int) $this->lines->sum('debit_cents');
        $credits = (int) $this->lines->sum('credit_cents');

        return $debits > 0 && $debits === $credits;
    }

    public function totalDebitCents(): int
    {
        $this->loadMissing('lines');

        return (int) $this->lines->sum('debit_cents');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
