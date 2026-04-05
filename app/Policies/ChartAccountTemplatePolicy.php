<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\ChartAccountTemplate;
use App\Models\User;

class ChartAccountTemplatePolicy
{
    public function viewAny(Admin|User $user): bool
    {
        return $user instanceof Admin;
    }

    public function view(Admin|User $user, ChartAccountTemplate $chartAccountTemplate): bool
    {
        return $user instanceof Admin;
    }

    public function create(Admin|User $user): bool
    {
        return $user instanceof Admin;
    }

    public function update(Admin|User $user, ChartAccountTemplate $chartAccountTemplate): bool
    {
        return $user instanceof Admin;
    }

    public function delete(Admin|User $user, ChartAccountTemplate $chartAccountTemplate): bool
    {
        return $user instanceof Admin;
    }
}
