<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use BelongsToCompany;

    protected static function booted(): void
    {
        static::creating(function (Member $member): void {
            if ($member->member_number !== null) {
                return;
            }

            $max = static::query()
                ->where('company_id', $member->company_id)
                ->max('member_number');

            $member->member_number = ((int) $max) + 1;
        });
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }

    protected $fillable = [
        'company_id',
        'reference_code',
        'name',
        'email',
        'phone',
        'address',
        'notes',
        'status',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function financialPositions(): HasMany
    {
        return $this->hasMany(FinancialPosition::class, 'member_id');
    }

    public function financeJournalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'member_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(MemberGroupMember::class);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
