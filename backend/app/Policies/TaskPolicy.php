<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Un recurso de otro usuario responde 404 (no 403) para no revelar que existe.
 */
class TaskPolicy
{
    public function view(User $user, Task $task): Response
    {
        return $this->owns($user, $task);
    }

    public function update(User $user, Task $task): Response
    {
        return $this->owns($user, $task);
    }

    public function delete(User $user, Task $task): Response
    {
        return $this->owns($user, $task);
    }

    private function owns(User $user, Task $task): Response
    {
        return $task->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
