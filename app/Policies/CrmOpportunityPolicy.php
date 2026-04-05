<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\CrmOpportunity;
use App\Models\User;

class CrmOpportunityPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canEditAccounting();
    }

    public function view(User|Admin $user, CrmOpportunity $crmOpportunity): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (! $user->canEditAccounting()) {
            return false;
        }

        return $user->isAdmin() || $user->company_id === $crmOpportunity->company_id;
    }

    public function create(User|Admin $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User|Admin $user, CrmOpportunity $crmOpportunity): bool
    {
        return $this->view($user, $crmOpportunity);
    }

    public function delete(User|Admin $user, CrmOpportunity $crmOpportunity): bool
    {
        return $this->view($user, $crmOpportunity);
    }
}
