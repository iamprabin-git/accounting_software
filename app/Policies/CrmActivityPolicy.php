<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\CrmActivity;
use App\Models\User;

class CrmActivityPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canEditAccounting();
    }

    public function view(User|Admin $user, CrmActivity $crmActivity): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (! $user->canEditAccounting()) {
            return false;
        }

        return $user->isAdmin() || $user->company_id === $crmActivity->company_id;
    }

    public function create(User|Admin $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User|Admin $user, CrmActivity $crmActivity): bool
    {
        return $this->view($user, $crmActivity);
    }

    public function delete(User|Admin $user, CrmActivity $crmActivity): bool
    {
        return $this->view($user, $crmActivity);
    }
}
