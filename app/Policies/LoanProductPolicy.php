<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\LoanProduct;
use App\Models\User;

class LoanProductPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canEditAccounting();
    }

    public function view(User|Admin $user, LoanProduct $loanProduct): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (! $user->canEditAccounting()) {
            return false;
        }

        return $user->isAdmin() || $user->company_id === $loanProduct->company_id;
    }

    public function create(User|Admin $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User|Admin $user, LoanProduct $loanProduct): bool
    {
        return $this->view($user, $loanProduct);
    }

    public function delete(User|Admin $user, LoanProduct $loanProduct): bool
    {
        return $this->view($user, $loanProduct);
    }
}
