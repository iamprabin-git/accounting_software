<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\CrmTenantGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmContact extends Model
{
    use BelongsToCompany;

    protected static function booted(): void
    {
        static::saving(function (CrmContact $contact): void {
            CrmTenantGuard::assertContact($contact);
        });
    }

    protected $table = 'crm_contacts';

    protected $fillable = [
        'company_id',
        'crm_account_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'job_title',
        'notes',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CrmAccount::class, 'crm_account_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(CrmOpportunity::class, 'crm_contact_id');
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
