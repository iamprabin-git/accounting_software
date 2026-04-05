<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\SavingsProduct;
use App\Models\User;

class SavingsProductPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canEditAccounting();
    }

    public function view(User|Admin $user, SavingsProduct $savingsProduct): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (! $user->canEditAccounting()) {
            return false;
        }

        return $user->isAdmin() || $user->company_id === $savingsProduct->company_id;
    }

    public function create(User|Admin $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User|Admin $user, SavingsProduct $savingsProduct): bool
    {
        return $this->view($user, $savingsProduct);
    }

    public function delete(User|Admin $user, SavingsProduct $savingsProduct): bool
    {
        return $this->view($user, $savingsProduct);
    }
}
