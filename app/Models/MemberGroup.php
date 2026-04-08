<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MemberGroup extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'notes',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_group_members')
            ->withTimestamps();
    }
}
