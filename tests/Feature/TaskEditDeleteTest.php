<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskEditDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function officer(string $name = 'Officer User'): User
    {
        return User::factory()->create([
            'name' => $name,
            'role' => 'officer',
        ]);
    }

    private function validTaskData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Updated Task Title',
            'description' => 'Updated task description details.',
            'due_date' => now()->addDays(5)->toDateString(),
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::InProgress->value,
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login_from_edit_update_and_delete(): void
    {
        $task = Task::factory()->forOfficer($this->officer())->create();

        $this->get(route('tasks.edit', $task))->assertRedirect(route('login'));
        $this->put(route('tasks.update', $task), $this->validTaskData())->assertRedirect(route('login'));
        $this->delete(route('tasks.destroy', $task))->assertRedirect(route('login'));
    }

    public function test_owner_sees_filled_edit_form(): void
    {
        $owner = $this->officer('Task Owner');
        $dueDate = now()->addDays(4)->startOfDay();
        $task = Task::factory()->forOfficer($owner)->create([
            'title' => 'Form Prefilled Title',
            'description' => 'Form prefilled description text.',
            'due_date' => $dueDate,
            'priority' => TaskPriority::High,
            'status' => TaskStatus::InProgress,
        ]);

        $response = $this->actingAs($owner)->get(route('tasks.edit', $task));

        $response->assertOk();
        $response->assertSee('value="Form Prefilled Title"', false);
        $response->assertSee('Form prefilled description text.');
        $response->assertSee($dueDate->toDateString());
        $response->assertSee($owner->name);
    }

    public function test_owner_can_update_their_task(): void
    {
        $owner = $this->officer();
        $task = Task::factory()->forOfficer($owner)->create([
            'title' => 'Initial Title',
            'description' => 'Initial description',
        ]);

        $response = $this->actingAs($owner)->put(route('tasks.update', $task), [
            'title' => 'New Valid Title',
            'description' => 'New valid description',
            'due_date' => now()->addDays(10)->toDateString(),
            'priority' => TaskPriority::Medium->value,
            'status' => TaskStatus::Done->value,
        ]);

        $response->assertRedirect(route('tasks.mine'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'New Valid Title',
            'description' => 'New valid description',
            'status' => TaskStatus::Done->value,
            'owner_id' => $owner->id,
        ]);
    }

    public function test_owner_can_delete_their_task(): void
    {
        $owner = $this->officer();
        $task = Task::factory()->forOfficer($owner)->create();

        $response = $this->actingAs($owner)->delete(route('tasks.destroy', $task));

        $response->assertRedirect(route('tasks.mine'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted($task);
    }

    public function test_another_user_gets_403_for_edit_form_update_and_delete_and_nothing_changes(): void
    {
        $owner = $this->officer('Daniel');
        $anotherUser = $this->officer('Amina');
        $task = Task::factory()->forOfficer($owner)->create([
            'title' => 'Daniel Original Task',
            'description' => 'Daniel original description',
            'priority' => TaskPriority::Low,
            'status' => TaskStatus::ToDo,
        ]);

        $this->actingAs($anotherUser)->get(route('tasks.edit', $task))->assertForbidden();

        $this->actingAs($anotherUser)->put(route('tasks.update', $task), [
            'title' => 'Amina Malicious Title',
            'description' => 'Amina malicious description',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::Done->value,
        ])->assertForbidden();

        $this->actingAs($anotherUser)->delete(route('tasks.destroy', $task))->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Daniel Original Task',
            'description' => 'Daniel original description',
            'priority' => TaskPriority::Low->value,
            'status' => TaskStatus::ToDo->value,
            'owner_id' => $owner->id,
        ]);
        $this->assertNotSoftDeleted($task);
    }

    public function test_update_is_validated(): void
    {
        $owner = $this->officer();
        $task = Task::factory()->forOfficer($owner)->create([
            'title' => 'Unchanged Valid Title',
        ]);

        $response = $this->actingAs($owner)->put(route('tasks.update', $task), [
            'title' => '',
            'description' => '',
            'due_date' => 'invalid-date',
            'priority' => 'invalid-priority',
            'status' => 'invalid-status',
        ]);

        $response->assertSessionHasErrors([
            'title',
            'description',
            'due_date',
            'priority',
            'status',
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Unchanged Valid Title',
        ]);
    }

    public function test_update_cannot_move_a_task_to_another_user_with_user_id_in_payload(): void
    {
        $owner = $this->officer('Amina');
        $anotherUser = $this->officer('Daniel');
        $task = Task::factory()->forOfficer($owner)->create([
            'title' => 'Amina Existing Task',
        ]);

        $response = $this->actingAs($owner)->put(route('tasks.update', $task), $this->validTaskData([
            'title' => 'Amina Updated Task',
            'user_id' => $anotherUser->id,
            'owner_id' => $anotherUser->id,
        ]));

        $response->assertRedirect(route('tasks.mine'));

        $freshTask = $task->fresh();
        $this->assertSame($owner->id, $freshTask->owner_id);
        $this->assertSame($owner->name, $freshTask->owner);
        $this->assertSame('Amina Updated Task', $freshTask->title);
    }

    public function test_list_shows_edit_and_delete_for_own_tasks(): void
    {
        $owner = $this->officer('Amina');
        $task = Task::factory()->forOfficer($owner)->create([
            'title' => 'Amina Task In List',
        ]);

        $response = $this->actingAs($owner)->get(route('tasks.mine'));

        $response->assertOk();
        $response->assertSee(route('tasks.edit', $task));
        $response->assertSee(route('tasks.destroy', $task));
        $response->assertSee(__('Edit'));
        $response->assertSee(__('Delete'));
    }

    public function test_edit_form_escapes_the_title(): void
    {
        $owner = $this->officer();
        $maliciousTitle = 'Audit <script>alert("xss")</script> Task';
        $task = Task::factory()->forOfficer($owner)->create([
            'title' => $maliciousTitle,
        ]);

        $response = $this->actingAs($owner)->get(route('tasks.edit', $task));

        $response->assertOk();
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee(e($maliciousTitle), false);
    }
}
