<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Creditor;
use App\Models\User;

class CreditorPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canEditAccounting();
    }

    public function view(User|Admin $user, Creditor $creditor): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (! $user->canEditAccounting()) {
            return false;
        }

        return $user->isAdmin() || $user->company_id === $creditor->company_id;
    }

    public function create(User|Admin $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User|Admin $user, Creditor $creditor): bool
    {
        return $this->view($user, $creditor);
    }

    public function delete(User|Admin $user, Creditor $creditor): bool
    {
        return $this->view($user, $creditor);
    }
}
