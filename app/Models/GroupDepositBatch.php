<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupDepositBatch extends Model
{
    protected $fillable = [
        'company_id',
        'member_group_id',
        'user_id',
        'transaction_date',
        'debit_chart_account_id',
        'reference',
        'memo',
        'total_cents',
        'line_count',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MemberGroup::class, 'member_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function debitChartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'debit_chart_account_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GroupDepositBatchLine::class);
    }
}
