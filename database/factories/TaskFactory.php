<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(12),
            'owner' => fake()->name(),
            'owner_id' => User::factory()->state(['role' => 'officer']),
            'due_date' => fake()->dateTimeBetween('tomorrow', '+2 months'),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'status' => TaskStatus::ToDo,
            'progress_note' => null,
            'progress_updated_at' => null,
        ];
    }

    public function forOfficer(User $officer): static
    {
        return $this->state(fn () => [
            'owner' => $officer->name,
            'owner_id' => $officer->id,
        ]);
    }

    public function overdue(int $daysAgo = 3): static
    {
        return $this->state(fn () => [
            'due_date' => today()->subDays(max(1, $daysAgo)),
        ]);
    }

    public function dueSoon(int $daysAhead = 2): static
    {
        return $this->state(fn () => [
            'due_date' => today()->addDays(max(0, min(3, $daysAhead))),
        ]);
    }

    public function dueLater(int $daysAhead = 14): static
    {
        return $this->state(fn () => [
            'due_date' => today()->addDays(max(8, $daysAhead)),
        ]);
    }

    public function done(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::Done,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::InProgress,
        ]);
    }

    public function stale(): static
    {
        return $this->state(fn () => [
            'progress_note' => fake()->sentence(8),
            'progress_updated_at' => now()->startOfWeek()->subDays(3),
        ]);
    }

    public function updatedThisWeek(): static
    {
        return $this->state(fn () => [
            'progress_note' => fake()->sentence(8),
            'progress_updated_at' => now()->startOfWeek()->addHours(12),
        ]);
    }
}
