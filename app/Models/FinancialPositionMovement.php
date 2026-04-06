<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialPositionMovement extends Model
{
    public const TYPE_OPENING = 'opening';

    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_DISBURSEMENT = 'disbursement';

    public const TYPE_INSTALLMENT = 'installment';

    public const TYPE_PENALTY = 'penalty';

    public const TYPE_INTEREST_RECEIPT = 'interest_receipt';

    protected $fillable = [
        'financial_position_id',
        'company_id',
        'user_id',
        'type',
        'amount_cents',
        'balance_after_cents',
        'memo',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'balance_after_cents' => 'integer',
        ];
    }

    public function financialPosition(): BelongsTo
    {
        return $this->belongsTo(FinancialPosition::class, 'financial_position_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
