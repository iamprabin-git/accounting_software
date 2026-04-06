<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JournalLine extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'chart_account_id',
        'debit_cents',
        'credit_cents',
        'description',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class);
    }

    public function bankStatementLineMatch(): HasOne
    {
        return $this->hasOne(BankStatementLineMatch::class);
    }

    public function netAmountCentsForBankAccount(): int
    {
        return (int) $this->debit_cents - (int) $this->credit_cents;
    }
}
