<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user->canEditAccounting();
    }

    public function view(User|Admin $user, InventoryItem $inventoryItem): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (! $user->canEditAccounting()) {
            return false;
        }

        return $user->isAdmin() || $user->company_id === $inventoryItem->company_id;
    }

    public function create(User|Admin $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User|Admin $user, InventoryItem $inventoryItem): bool
    {
        return $this->view($user, $inventoryItem);
    }

    public function delete(User|Admin $user, InventoryItem $inventoryItem): bool
    {
        return $this->view($user, $inventoryItem);
    }
}
