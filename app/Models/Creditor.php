<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Creditor extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'reference',
        'balance_cents',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'balance_cents' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
