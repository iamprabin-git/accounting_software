<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\FinancialPosition;
use App\Models\User;

class FinancialPositionPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canEditAccounting();
    }

    public function view(User|Admin $user, FinancialPosition $financialPosition): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (! $user->canEditAccounting()) {
            return false;
        }

        return $user->isAdmin() || $user->company_id === $financialPosition->company_id;
    }

    public function create(User|Admin $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User|Admin $user, FinancialPosition $financialPosition): bool
    {
        return $this->view($user, $financialPosition);
    }

    public function delete(User|Admin $user, FinancialPosition $financialPosition): bool
    {
        return $this->view($user, $financialPosition);
    }

    public function approve(User|Admin $user, FinancialPosition $financialPosition): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->isCompany() && $user->company_id === $financialPosition->company_id;
    }

    public function reject(User|Admin $user, FinancialPosition $financialPosition): bool
    {
        return $this->approve($user, $financialPosition);
    }
}
