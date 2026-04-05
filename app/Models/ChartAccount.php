<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartAccount extends Model
{
    use BelongsToCompany;

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PENDING = 'pending';

    public const TYPE_ASSET = 'asset';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_EQUITY = 'equity';

    public const TYPE_REVENUE = 'revenue';

    public const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'company_id',
        'chart_account_template_id',
        'user_id',
        'code',
        'name',
        'type',
        'description',
        'approval_status',
        'approved_at',
        'approved_by_user_id',
        'approved_by_admin_id',
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

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChartAccountTemplate::class, 'chart_account_template_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_APPROVED;
    }

    public function scopeApprovedForJournals($query)
    {
        return $query->where('approval_status', self::STATUS_APPROVED);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
