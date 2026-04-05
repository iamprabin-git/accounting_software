<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Review;

class ReviewPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return true;
    }

    public function view(Admin $admin, Review $review): bool
    {
        return true;
    }

    public function create(Admin $admin): bool
    {
        return true;
    }

    public function update(Admin $admin, Review $review): bool
    {
        return true;
    }

    public function delete(Admin $admin, Review $review): bool
    {
        return true;
    }
}
