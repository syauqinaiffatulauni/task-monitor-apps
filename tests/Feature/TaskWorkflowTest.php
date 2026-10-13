<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function task(User $owner, array $attributes = []): Task
    {
        return Task::create($attributes + [
            'title' => 'Sample monitoring task', 'description' => 'A fictional task.',
            'owner' => $owner->name, 'owner_id' => $owner->id,
            'due_date' => now()->addWeek(), 'priority' => 'high', 'status' => 'to do',
        ]);
    }

    public function test_officer_my_tasks_only_contains_assigned_tasks(): void
    {
        $officer = $this->user('officer');
        $other = $this->user('officer');
        $this->task($officer, ['title' => 'Assigned task']);
        $this->task($other, ['title' => 'Other task']);

        $this->actingAs($officer)->get('/my-tasks')->assertOk()->assertSee('Assigned task')->assertDontSee('Other task');
        $this->actingAs($officer)->get('/tasks')->assertOk()->assertSee('Assigned task')->assertSee('Other task');
    }

    public function test_every_task_form_field_is_validated(): void
    {
        $this->actingAs($this->user('officer'))->post('/tasks', [])->assertSessionHasErrors([
            'title', 'description', 'owner_id', 'due_date', 'priority', 'status',
        ]);
    }

    public function test_validated_task_is_created_for_an_officer(): void
    {
        $creator = $this->user('supervisor');
        $owner = $this->user('officer');
        $this->actingAs($creator)->post('/tasks', [
            'title' => 'Prepare weekly update', 'description' => 'Summarize current work.',
            'owner_id' => $owner->id, 'due_date' => '2026-12-01',
            'priority' => 'medium', 'status' => 'to do',
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', ['title' => 'Prepare weekly update', 'owner_id' => $owner->id]);
    }

    public function test_overdue_filter_only_lists_incomplete_overdue_tasks(): void
    {
        $officer = $this->user('officer');
        $this->task($officer, ['title' => 'Overdue active task', 'due_date' => now()->subDay()]);
        $this->task($officer, ['title' => 'Future active task', 'due_date' => now()->addDay()]);
        $this->task($officer, ['title' => 'Completed overdue task', 'due_date' => now()->subDay(), 'status' => 'done']);

        $this->actingAs($officer)->get('/tasks?overdue=1')
            ->assertOk()
            ->assertSee('Overdue active task')
            ->assertDontSee('Future active task')
            ->assertDontSee('Completed overdue task');
    }

    public function test_supervisor_dashboard_shows_monitoring_statistics(): void
    {
        $supervisor = $this->user('supervisor');
        $officer = $this->user('officer');
        $this->task($officer, ['title' => 'Overdue dashboard task', 'due_date' => now()->subDay(), 'progress_updated_at' => null]);
        $this->task($officer, ['title' => 'Completed dashboard task', 'status' => 'done']);

        $this->actingAs($supervisor)->get('/supervisor')
            ->assertOk()
            ->assertSee('Supervisor overview')
            ->assertSee('Total tasks')
            ->assertSee('Active tasks')
            ->assertSee('Overdue tasks')
            ->assertSee('Completed tasks')
            ->assertSee('Not updated this week');

        $this->actingAs($supervisor)->get('/supervisor?view=overdue')
            ->assertOk()
            ->assertSee('Overdue dashboard task')
            ->assertDontSee('Completed dashboard task');

        $this->actingAs($supervisor)->get('/supervisor?view=completed')
            ->assertOk()
            ->assertSee('Completed dashboard task')
            ->assertDontSee('Overdue dashboard task');
    }
}
