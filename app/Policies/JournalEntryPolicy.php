<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canViewAccountingReports();
    }

    public function view(User|Admin $user, JournalEntry $journalEntry): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->company_id === $journalEntry->company_id;
    }

    public function create(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canCreateJournalEntries()
            && ($user->isAdmin() || $user->company_id !== null);
    }

    public function update(User|Admin $user, JournalEntry $journalEntry): bool
    {
        if (! $this->view($user, $journalEntry)) {
            return false;
        }

        if ($user instanceof Admin) {
            return $journalEntry->isDraft() || $journalEntry->isRejected();
        }

        if (! $user->canCreateJournalEntries()) {
            return false;
        }

        return $journalEntry->isDraft() || $journalEntry->isRejected();
    }

    public function delete(User|Admin $user, JournalEntry $journalEntry): bool
    {
        return $this->update($user, $journalEntry);
    }

    public function submit(User|Admin $user, JournalEntry $journalEntry): bool
    {
        if (! $this->view($user, $journalEntry)) {
            return false;
        }

        if ($user instanceof Admin) {
            return $journalEntry->isDraft();
        }

        return $user->canCreateJournalEntries()
            && $journalEntry->isDraft();
    }

    public function approve(User|Admin $user, JournalEntry $journalEntry): bool
    {
        if ($user instanceof Admin) {
            return $journalEntry->isPending();
        }

        if (! $user->canApproveJournalEntries()) {
            return false;
        }

        if (! $user->isAdmin() && $user->company_id !== $journalEntry->company_id) {
            return false;
        }

        if (
            $journalEntry->first_approved_by_user_id !== null
            && (int) $journalEntry->first_approved_by_user_id === (int) $user->id
        ) {
            return false;
        }

        return $journalEntry->isPending();
    }

    public function reject(User|Admin $user, JournalEntry $journalEntry): bool
    {
        if ($user instanceof Admin) {
            return $journalEntry->isPending();
        }

        if (! $user->canApproveJournalEntries()) {
            return false;
        }

        if (! $user->isAdmin() && $user->company_id !== $journalEntry->company_id) {
            return false;
        }

        return $journalEntry->isPending();
    }
}
