<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupLoanCollectionBatch extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'member_group_id',
        'user_id',
        'transaction_date',
        'debit_chart_account_id',
        'interest_revenue_chart_account_id',
        'penalty_credit_chart_account_id',
        'reference',
        'memo',
        'total_principal_cents',
        'total_interest_cents',
        'total_penalty_cents',
        'line_count',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
        ];
    }

    public function memberGroup(): BelongsTo
    {
        return $this->belongsTo(MemberGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function debitChartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'debit_chart_account_id');
    }

    public function interestRevenueChartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'interest_revenue_chart_account_id');
    }

    public function penaltyCreditChartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'penalty_credit_chart_account_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GroupLoanCollectionBatchLine::class);
    }
}
