<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\CrmContact;
use App\Models\User;

class CrmContactPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canEditAccounting();
    }

    public function view(User|Admin $user, CrmContact $crmContact): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (! $user->canEditAccounting()) {
            return false;
        }

        return $user->isAdmin() || $user->company_id === $crmContact->company_id;
    }

    public function create(User|Admin $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User|Admin $user, CrmContact $crmContact): bool
    {
        return $this->view($user, $crmContact);
    }

    public function delete(User|Admin $user, CrmContact $crmContact): bool
    {
        return $this->view($user, $crmContact);
    }
}
