<?php

namespace Database\Seeders\Policies;

use App\Users\Models\User;

class CanUpdateCounters
{
    public function update(User $user)
    {
        return $user->isAdmin(); // Replace with your own authorization logic
    }
}
