<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupDepositBatchLine extends Model
{
    protected $fillable = [
        'group_deposit_batch_id',
        'member_id',
        'financial_position_id',
        'amount_cents',
        'journal_entry_id',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(GroupDepositBatch::class, 'group_deposit_batch_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function financialPosition(): BelongsTo
    {
        return $this->belongsTo(FinancialPosition::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
