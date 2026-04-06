<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementLine extends Model
{
    protected $fillable = [
        'bank_reconciliation_batch_id',
        'transaction_date',
        'amount_cents',
        'description',
        'external_reference',
        'reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'reconciled_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BankReconciliationBatch::class, 'bank_reconciliation_batch_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(BankStatementLineMatch::class);
    }

    /**
     * Fully reconciled when matched journal nets sum to the statement amount.
     */
    public function isFullyMatched(): bool
    {
        return $this->reconciled_at !== null;
    }
}
