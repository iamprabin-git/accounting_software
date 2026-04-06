<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliationBatch extends Model
{
    protected $fillable = [
        'company_id',
        'chart_account_id',
        'user_id',
        'name',
        'statement_opening_balance_cents',
        'statement_closing_balance_cents',
    ];

    protected function casts(): array
    {
        return [
            'statement_opening_balance_cents' => 'integer',
            'statement_closing_balance_cents' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'chart_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statementLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }
}
