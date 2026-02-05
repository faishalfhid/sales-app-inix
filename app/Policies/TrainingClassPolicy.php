<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TrainingClass;

class TrainingClassPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TrainingClass $trainingClass): bool
    {
        if ($user->role === 'staff') {
            return $trainingClass->sales_id === $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TrainingClass $trainingClass): bool
    {
        if ($user->role === 'staff') {
            return $trainingClass->sales_id === $user->id;
        }

        return true;
    }

    public function delete(User $user, TrainingClass $trainingClass): bool
    {
        if ($user->role === 'staff') {
            return $trainingClass->sales_id === $user->id;
        }

        return true;
    }
}
