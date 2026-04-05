<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsProduct extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_code',
        'name',
        'default_annual_interest_rate_percent',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_annual_interest_rate_percent' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function financialPositions(): HasMany
    {
        return $this->hasMany(FinancialPosition::class, 'savings_product_id');
    }
}
