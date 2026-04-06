<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupLoanCollectionBatchLine extends Model
{
    protected $fillable = [
        'group_loan_collection_batch_id',
        'member_id',
        'financial_position_id',
        'principal_cents',
        'interest_cents',
        'penalty_cents',
        'principal_journal_entry_id',
        'interest_journal_entry_id',
        'penalty_journal_entry_id',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(GroupLoanCollectionBatch::class, 'group_loan_collection_batch_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function financialPosition(): BelongsTo
    {
        return $this->belongsTo(FinancialPosition::class);
    }
}
