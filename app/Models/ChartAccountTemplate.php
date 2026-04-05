<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartAccountTemplate extends Model
{
    public const TYPE_ASSET = 'asset';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_EQUITY = 'equity';

    public const TYPE_REVENUE = 'revenue';

    public const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function chartAccounts(): HasMany
    {
        return $this->hasMany(ChartAccount::class, 'chart_account_template_id');
    }
}
