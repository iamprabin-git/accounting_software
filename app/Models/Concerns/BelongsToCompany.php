<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->company_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            $query->getModel()->getTable().'.company_id',
            $user->company_id
        );
    }
}
