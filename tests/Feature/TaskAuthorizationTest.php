<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function task(User $owner): Task
    {
        return Task::create(['title' => 'Policy task', 'description' => 'Test', 'owner' => $owner->name, 'owner_id' => $owner->id, 'due_date' => now()->addDay(), 'priority' => 'medium', 'status' => 'to do']);
    }

    public function test_both_roles_can_create_tasks(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'officer']))->get('/tasks/create')->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'supervisor']))->get('/tasks/create')->assertOk();
    }

    public function test_officer_can_update_only_assigned_task_progress(): void
    {
        $owner = User::factory()->create(['role' => 'officer']);
        $other = User::factory()->create(['role' => 'officer']);
        $task = $this->task($owner);
        $data = ['status' => 'in progress', 'progress_note' => 'Work has started.'];

        $this->actingAs($owner)->put(route('tasks.progress.update', $task), $data)->assertRedirect();
        $this->actingAs($other)->put(route('tasks.progress.update', $task), $data)->assertForbidden();
        $this->actingAs($owner)->get(route('tasks.edit', $task))->assertOk();
    }

    public function test_supervisor_can_edit_every_task_field(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $owner = User::factory()->create(['role' => 'officer']);
        $task = $this->task($owner);

        $this->actingAs($supervisor)->put(route('tasks.update', $task), [
            'title' => 'Changed by supervisor', 'description' => 'Updated', 'owner_id' => $owner->id,
            'due_date' => '2026-12-02', 'priority' => 'low', 'status' => 'done', 'progress_note' => 'Complete',
        ])->assertRedirect(route('tasks.show', $task));
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Changed by supervisor', 'status' => 'done']);
    }

    public function test_assigned_officer_and_supervisor_can_quick_update_status(): void
    {
        $owner = User::factory()->create(['role' => 'officer']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $task = $this->task($owner);

        $this->actingAs($owner)
            ->patch(route('tasks.status.update', $task), ['status' => 'in progress'])
            ->assertRedirect();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'in progress']);

        $this->actingAs($supervisor)
            ->patch(route('tasks.status.update', $task), ['status' => 'done'])
            ->assertRedirect();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'done']);
    }

    public function test_unassigned_officer_cannot_quick_update_status(): void
    {
        $owner = User::factory()->create(['role' => 'officer']);
        $other = User::factory()->create(['role' => 'officer']);
        $task = $this->task($owner);

        $this->actingAs($other)
            ->patch(route('tasks.status.update', $task), ['status' => 'done'])
            ->assertForbidden();
    }

    public function test_assigned_officer_can_archive_but_supervisor_cannot(): void
    {
        $owner = User::factory()->create(['role' => 'officer']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $officerTask = $this->task($owner);
        $supervisorTask = $this->task($owner);

        $this->actingAs($owner)->delete(route('tasks.destroy', $officerTask))->assertRedirect(route('tasks.mine'));
        $this->assertSoftDeleted($officerTask);

        $this->actingAs($supervisor)->delete(route('tasks.destroy', $supervisorTask))->assertForbidden();
        $this->assertNotSoftDeleted($supervisorTask);
    }

    public function test_unassigned_officer_cannot_archive_task(): void
    {
        $owner = User::factory()->create(['role' => 'officer']);
        $other = User::factory()->create(['role' => 'officer']);
        $task = $this->task($owner);

        $this->actingAs($other)->delete(route('tasks.destroy', $task))->assertForbidden();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
    }
}
