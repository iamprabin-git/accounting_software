<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canEditAccounting();
    }

    public function view(User|Admin $user, Member $member): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (! $user->canEditAccounting()) {
            return false;
        }

        return $user->isAdmin() || $user->company_id === $member->company_id;
    }

    /**
     * Staff (and platform admin) register new members; company owners do not create records here.
     */
    public function create(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->isStaff() || $user->isAdmin();
    }

    public function update(User|Admin $user, Member $member): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if ($user->company_id !== $member->company_id) {
            return false;
        }

        if ($user->isCompany()) {
            return true;
        }

        if ($user->isStaff()) {
            return $member->isPending();
        }

        return false;
    }

    public function delete(User|Admin $user, Member $member): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->company_id !== $member->company_id) {
            return false;
        }

        if ($user->isCompany()) {
            return true;
        }

        if ($user->isStaff()) {
            return $member->isPending() || $member->status === Member::STATUS_REJECTED;
        }

        return false;
    }

    public function approve(User|Admin $user, Member $member): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isCompany()) {
            return false;
        }

        return $user->company_id === $member->company_id;
    }

    public function reject(User|Admin $user, Member $member): bool
    {
        return $this->approve($user, $member);
    }
}
