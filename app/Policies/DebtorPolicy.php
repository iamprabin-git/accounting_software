<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Debtor;
use App\Models\User;

class DebtorPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canEditAccounting();
    }

    public function view(User|Admin $user, Debtor $debtor): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (! $user->canEditAccounting()) {
            return false;
        }

        return $user->isAdmin() || $user->company_id === $debtor->company_id;
    }

    public function create(User|Admin $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User|Admin $user, Debtor $debtor): bool
    {
        return $this->view($user, $debtor);
    }

    public function delete(User|Admin $user, Debtor $debtor): bool
    {
        return $this->view($user, $debtor);
    }
}
