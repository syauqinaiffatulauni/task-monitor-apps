<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function viewAll(User $user): bool
    {
        return $user->isSupervisor();
    }

    public function view(User $user, Task $task): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['officer', 'supervisor'], true);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->isSupervisor() || $task->owner_id === $user->id;
    }

    public function updateProgress(User $user, Task $task): bool
    {
        return $user->isSupervisor() || $task->owner_id === $user->id;
    }

    public function changeStatus(User $user, Task $task): bool
    {
        return $user->isSupervisor() || $task->owner_id === $user->id;
    }

    public function changeDueDate(User $user, Task $task): bool
    {
        return $user->isSupervisor();
    }

    public function delete(User $user, Task $task): bool
    {
        return ! $user->isSupervisor() && $task->owner_id === $user->id;
    }
}
