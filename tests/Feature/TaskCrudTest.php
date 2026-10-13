<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCrudTest extends TestCase
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

    private function validTaskData(User $owner): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(10),
            'owner_id' => $owner->id,
            'due_date' => now()->addWeek()->toDateString(),
            'priority' => TaskPriority::Medium->value,
            'status' => TaskStatus::ToDo->value,
        ];
    }

    public function test_authorized_user_can_create_a_valid_task(): void
    {
        $creator = $this->officer();
        $owner = $this->officer();
        $data = $this->validTaskData($owner);

        $this->actingAs($creator)->post(route('tasks.store'), $data)
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'title' => $data['title'],
            'owner_id' => $owner->id,
        ]);
    }

    public function test_supervisor_can_edit_a_task_with_valid_data(): void
    {
        $supervisor = $this->supervisor();
        $owner = $this->officer();
        $task = Task::factory()->forOfficer($owner)->create();
        $data = $this->validTaskData($owner) + ['progress_note' => fake()->sentence()];

        $this->actingAs($supervisor)->put(route('tasks.update', $task), $data)
            ->assertRedirect(route('tasks.show', $task))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => $data['title']]);
    }

    public function test_assigned_officer_can_soft_delete_a_task(): void
    {
        $officer = $this->officer();
        $task = Task::factory()->forOfficer($officer)->create();

        $this->actingAs($officer)->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.mine'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($task);
    }

    public function test_create_rejects_missing_and_invalid_fields(): void
    {
        $this->actingAs($this->officer())->post(route('tasks.store'), [
            'title' => '',
            'description' => '',
            'owner_id' => 'not-an-id',
            'due_date' => 'not-a-date',
            'priority' => 'urgent',
            'status' => 'unknown',
        ])->assertSessionHasErrors([
            'title', 'description', 'owner_id', 'due_date', 'priority', 'status',
        ]);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_edit_rejects_invalid_fields_and_keeps_original_values(): void
    {
        $supervisor = $this->supervisor();
        $owner = $this->officer();
        $task = Task::factory()->forOfficer($owner)->create();

        $this->actingAs($supervisor)->put(route('tasks.update', $task), [
            'title' => '', 'description' => '', 'owner_id' => 'invalid',
            'due_date' => 'invalid', 'priority' => 'urgent', 'status' => 'unknown',
        ])->assertSessionHasErrors([
            'title', 'description', 'owner_id', 'due_date', 'priority', 'status',
        ]);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => $task->title]);
    }

    public function test_quick_status_rejects_a_non_enum_value(): void
    {
        $officer = $this->officer();
        $task = Task::factory()->forOfficer($officer)->create();

        $this->actingAs($officer)->patch(route('tasks.status.update', $task), [
            'status' => 'unknown',
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => TaskStatus::ToDo->value]);
    }

    public function test_officer_can_view_all_tasks_but_cannot_edit_or_delete_another_officers_task(): void
    {
        $officer = $this->officer();
        $otherOfficer = $this->officer();
        $task = Task::factory()->forOfficer($otherOfficer)->create();

        $this->actingAs($officer)->get(route('tasks.show', $task))->assertOk();
        $this->actingAs($officer)->get(route('tasks.index'))
            ->assertOk()
            ->assertSee($task->title)
            ->assertDontSee(route('tasks.edit', $task));
        $this->actingAs($officer)->get(route('tasks.edit', $task))->assertForbidden();
        $this->actingAs($officer)->put(route('tasks.update', $task), $this->validTaskData($otherOfficer))->assertForbidden();
        $this->actingAs($officer)->delete(route('tasks.destroy', $task))->assertForbidden();
        $this->assertNotSoftDeleted($task);
    }

    public function test_supervisor_sees_all_tasks_and_can_change_status_but_cannot_delete(): void
    {
        $supervisor = $this->supervisor();
        $first = Task::factory()->forOfficer($this->officer())->create();
        $second = Task::factory()->forOfficer($this->officer())->create();

        $this->actingAs($supervisor)->get(route('tasks.index'))
            ->assertOk()->assertSee($first->title)->assertSee($second->title);
        $this->actingAs($supervisor)->patch(route('tasks.status.update', $first), [
            'status' => TaskStatus::Done->value,
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('tasks', ['id' => $first->id, 'status' => TaskStatus::Done->value]);

        $this->actingAs($supervisor)->delete(route('tasks.destroy', $second))->assertForbidden();
        $this->assertNotSoftDeleted($second);
    }

    public function test_guest_is_redirected_to_login_from_protected_task_routes(): void
    {
        $task = Task::factory()->forOfficer($this->officer())->create();

        $this->get(route('tasks.index'))->assertRedirect(route('login'));
        $this->get(route('tasks.show', $task))->assertRedirect(route('login'));
        $this->get(route('tasks.create'))->assertRedirect(route('login'));
    }

    public function test_filters_return_only_matching_rows(): void
    {
        $officer = $this->officer();
        $matching = Task::factory()->forOfficer($officer)->create([
            'title' => 'Matching fictional task',
            'priority' => TaskPriority::High,
            'status' => TaskStatus::InProgress,
            'due_date' => now()->subDay(),
        ]);
        $nonMatching = Task::factory()->forOfficer($officer)->create([
            'title' => 'Different fictional task',
            'priority' => TaskPriority::Low,
            'status' => TaskStatus::ToDo,
            'due_date' => now()->addDay(),
        ]);

        $this->actingAs($officer)->get(route('tasks.index', [
            'search' => 'Matching',
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::InProgress->value,
            'overdue' => 1,
        ]))->assertOk()->assertSee($matching->title)->assertDontSee($nonMatching->title);
    }
}
