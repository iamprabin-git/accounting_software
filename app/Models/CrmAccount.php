<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmAccount extends Model
{
    use BelongsToCompany;

    protected $table = 'crm_accounts';

    protected $fillable = [
        'company_id',
        'name',
        'industry',
        'website',
        'phone',
        'email',
        'address',
        'notes',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CrmContact::class, 'crm_account_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(CrmOpportunity::class, 'crm_account_id');
    }
}
