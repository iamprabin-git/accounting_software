<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberGroup extends Model
{
    use BelongsToCompany;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'meeting_day',
        'status',
        'created_by_user_id',
        'notes',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(MemberGroupMember::class);
    }

    public function depositBatches(): HasMany
    {
        return $this->hasMany(GroupDepositBatch::class);
    }

    public function loanCollectionBatches(): HasMany
    {
        return $this->hasMany(GroupLoanCollectionBatch::class);
    }
}
