<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficerAllTasksViewTest extends TestCase
{
    use RefreshDatabase;

    private function officer(): User
    {
        return User::factory()->create(['role' => 'officer']);
    }

    private function supervisor(): User
    {
        return User::factory()->create(['role' => 'supervisor']);
    }

    private function task(User $owner, array $attributes = []): Task
    {
        return Task::create($attributes + [
            'title' => 'Assigned task '.fake()->uuid(),
            'description' => 'A fictional task.',
            'owner' => $owner->name,
            'owner_id' => $owner->id,
            'due_date' => now()->addWeek()->toDateString(),
            'priority' => TaskPriority::Medium->value,
            'status' => TaskStatus::ToDo->value,
        ]);
    }

    public function test_officer_sees_all_officers_tasks_on_all_tasks_page(): void
    {
        $officer1 = $this->officer();
        $officer2 = $this->officer();

        $task1 = $this->task($officer1, ['title' => 'Task for Officer 1']);
        $task2 = $this->task($officer2, ['title' => 'Task for Officer 2']);

        $response = $this->actingAs($officer1)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertSee('Task for Officer 1');
        $response->assertSee('Task for Officer 2');
    }

    public function test_officer_sees_only_own_tasks_on_my_tasks_page(): void
    {
        $officer1 = $this->officer();
        $officer2 = $this->officer();

        $task1 = $this->task($officer1, ['title' => 'Personal Task Officer 1']);
        $task2 = $this->task($officer2, ['title' => 'Personal Task Officer 2']);

        $response = $this->actingAs($officer1)->get(route('tasks.mine'));

        $response->assertOk();
        $response->assertSee('Personal Task Officer 1');
        $response->assertDontSee('Personal Task Officer 2');
    }

    public function test_officer_can_view_show_page_for_another_officers_task(): void
    {
        $officer1 = $this->officer();
        $officer2 = $this->officer();

        $task2 = $this->task($officer2, ['title' => 'Officer 2 Confidential Work']);

        $response = $this->actingAs($officer1)->get(route('tasks.show', $task2));

        $response->assertOk();
        $response->assertSee('Officer 2 Confidential Work');
        // Action buttons and progress forms should not appear for non-owner
        $response->assertDontSee(route('tasks.edit', $task2));
        $response->assertDontSee('action="'.route('tasks.destroy', $task2).'"', false);
        $response->assertDontSee(route('tasks.progress.update', $task2));
    }

    public function test_officer_does_not_see_edit_or_delete_buttons_for_another_officers_task_on_all_tasks(): void
    {
        $officer1 = $this->officer();
        $officer2 = $this->officer();

        $ownTask = $this->task($officer1, ['title' => 'Own Task']);
        $otherTask = $this->task($officer2, ['title' => 'Other Task']);

        $response = $this->actingAs($officer1)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertSee('action="'.route('tasks.destroy', $ownTask).'"', false);

        $response->assertDontSee(route('tasks.edit', $otherTask));
        $response->assertDontSee('action="'.route('tasks.destroy', $otherTask).'"', false);
    }

    public function test_officer_cannot_edit_another_officers_task_and_gets_403(): void
    {
        $officer1 = $this->officer();
        $officer2 = $this->officer();

        $task2 = $this->task($officer2);

        $this->actingAs($officer1)->get(route('tasks.edit', $task2))->assertForbidden();
    }

    public function test_officer_cannot_update_another_officers_task_and_gets_403(): void
    {
        $officer1 = $this->officer();
        $officer2 = $this->officer();

        $task2 = $this->task($officer2, ['title' => 'Original Title']);

        $this->actingAs($officer1)->put(route('tasks.update', $task2), [
            'title' => 'Hacked Title',
            'description' => 'Changed description',
            'due_date' => now()->addDays(5)->toDateString(),
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::InProgress->value,
        ])->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task2->id,
            'title' => 'Original Title',
        ]);
    }

    public function test_officer_cannot_delete_another_officers_task_and_gets_403(): void
    {
        $officer1 = $this->officer();
        $officer2 = $this->officer();

        $task2 = $this->task($officer2);

        $this->actingAs($officer1)->delete(route('tasks.destroy', $task2))->assertForbidden();

        $this->assertNotSoftDeleted($task2);
    }

    public function test_supervisor_permissions_remain_unchanged(): void
    {
        $supervisor = $this->supervisor();
        $officer = $this->officer();

        $task = $this->task($officer, ['title' => 'Officer Task']);

        // Supervisor can view all tasks on /tasks
        $this->actingAs($supervisor)->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Officer Task');

        // Supervisor can view show page
        $this->actingAs($supervisor)->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Officer Task');

        // Supervisor can edit
        $this->actingAs($supervisor)->get(route('tasks.edit', $task))
            ->assertOk();

        // Supervisor can update
        $this->actingAs($supervisor)->put(route('tasks.update', $task), [
            'title' => 'Supervisor Updated Title',
            'description' => 'Updated by supervisor',
            'owner_id' => $officer->id,
            'due_date' => now()->addDays(3)->toDateString(),
            'priority' => TaskPriority::Low->value,
            'status' => TaskStatus::Done->value,
        ])->assertRedirect(route('tasks.show', $task));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Supervisor Updated Title',
        ]);

        // Supervisor CANNOT delete (unchanged rule)
        $this->actingAs($supervisor)->delete(route('tasks.destroy', $task))
            ->assertForbidden();

        $this->assertNotSoftDeleted($task);
    }
}
