<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\CrmAccount;
use App\Models\User;

class CrmAccountPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canEditAccounting();
    }

    public function view(User|Admin $user, CrmAccount $crmAccount): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (! $user->canEditAccounting()) {
            return false;
        }

        return $user->isAdmin() || $user->company_id === $crmAccount->company_id;
    }

    public function create(User|Admin $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User|Admin $user, CrmAccount $crmAccount): bool
    {
        return $this->view($user, $crmAccount);
    }

    public function delete(User|Admin $user, CrmAccount $crmAccount): bool
    {
        return $this->view($user, $crmAccount);
    }
}
