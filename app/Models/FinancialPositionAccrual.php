<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialPositionAccrual extends Model
{
    public const KIND_LOAN_MONTHLY = 'loan_monthly';

    public const KIND_SAVINGS_MONTHLY = 'savings_monthly';

    public const KIND_INVESTMENT_MANUAL = 'investment_manual';

    protected $fillable = [
        'financial_position_id',
        'company_id',
        'accrual_year',
        'accrual_month',
        'amount_cents',
        'kind',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'accrual_year' => 'integer',
            'accrual_month' => 'integer',
            'amount_cents' => 'integer',
        ];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(FinancialPosition::class, 'financial_position_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function isPosted(): bool
    {
        return $this->journal_entry_id !== null;
    }
}
