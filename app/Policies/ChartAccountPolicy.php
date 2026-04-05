<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\ChartAccount;
use App\Models\User;

class ChartAccountPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canManageChartOfAccounts() || $user->canViewAccountingReports();
    }

    public function view(User|Admin $user, ChartAccount $chartAccount): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->company_id !== null
            && $user->company_id === $chartAccount->company_id;
    }

    public function create(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if ($user->company_id === null) {
            return false;
        }

        return $user->canManageChartOfAccounts() || $user->isStaff();
    }

    public function update(User|Admin $user, ChartAccount $chartAccount): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isCompany() && $user->company_id === $chartAccount->company_id;
    }

    public function delete(User|Admin $user, ChartAccount $chartAccount): bool
    {
        return $this->update($user, $chartAccount);
    }

    public function approve(User|Admin $user, ChartAccount $chartAccount): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (! $user->canApproveChartAccounts()) {
            return false;
        }

        if ($user->company_id !== $chartAccount->company_id) {
            return false;
        }

        return $chartAccount->approval_status === ChartAccount::STATUS_PENDING;
    }

    public function reject(User|Admin $user, ChartAccount $chartAccount): bool
    {
        return $this->approve($user, $chartAccount);
    }
}
