<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'task_date' => '2026-10-15',
            'start_time' => '09:00',
        ];
    }

    public function allDay(): static
    {
        return $this->state(['start_time' => null, 'end_time' => null]);
    }

    /**
     * @param  list<int>|null  $days
     */
    public function recurring(string $recurrence, ?array $days = null): static
    {
        return $this->state(['recurrence' => $recurrence, 'recurrence_days' => $days]);
    }
}
