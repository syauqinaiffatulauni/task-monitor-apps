<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    private function eventParameters(array $overrides = []): array
    {
        return $overrides + [
            'start' => '2026-10-01',
            'end' => '2026-11-01',
        ];
    }

    public function test_guest_is_redirected_from_calendar_events_to_login(): void
    {
        $this->get(route('calendar.events', $this->eventParameters()))
            ->assertRedirect(route('login'));
    }

    public function test_officer_calendar_page_loads_without_owner_filter(): void
    {
        $officer = User::factory()->create(['role' => 'officer']);

        $this->actingAs($officer)->get(route('calendar'))
            ->assertOk()
            ->assertSee('id="task-calendar"', false)
            ->assertDontSee('id="calendar-owner"', false);
    }

    public function test_supervisor_calendar_page_loads_with_owner_filter(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $owner = User::factory()->create(['role' => 'officer']);

        $this->actingAs($supervisor)->get(route('calendar'))
            ->assertOk()
            ->assertSee('id="task-calendar"', false)
            ->assertSee('id="calendar-owner"', false)
            ->assertSee($owner->name);
    }

    public function test_create_form_only_prefills_a_real_iso_due_date(): void
    {
        $officer = User::factory()->create(['role' => 'officer']);

        $this->actingAs($officer)->get(route('tasks.create', ['due_date' => '2026-02-28']))
            ->assertOk()
            ->assertSee('value="2026-02-28"', false);
        $this->actingAs($officer)->get(route('tasks.create', ['due_date' => '2026-02-30']))
            ->assertOk()
            ->assertDontSee('value="2026-02-30"', false);
    }

    public function test_calendar_requires_authentication_and_valid_dates(): void
    {
        $this->get(route('calendar'))->assertRedirect(route('login'));

        $officer = User::factory()->create(['role' => 'officer']);
        $this->actingAs($officer)->getJson(route('calendar.events'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start', 'end']);
        $this->actingAs($officer)->getJson(route('calendar.events', [
            'start' => '2026-10-10',
            'end' => '2026-10-01',
        ]))->assertUnprocessable()->assertJsonValidationErrors('end');
    }

    public function test_task_before_start_is_excluded_and_task_on_start_is_included(): void
    {
        $officer = User::factory()->create(['role' => 'officer']);
        $before = Task::factory()->forOfficer($officer)->create(['due_date' => '2026-09-30']);
        $onStart = Task::factory()->forOfficer($officer)->create(['due_date' => '2026-10-01']);

        $this->actingAs($officer)->getJson(route('calendar.events', $this->eventParameters()))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $onStart->id)
            ->assertJsonMissing(['id' => $before->id]);
    }

    public function test_officer_events_are_scoped_and_end_date_is_exclusive(): void
    {
        $officer = User::factory()->create(['role' => 'officer']);
        $other = User::factory()->create(['role' => 'officer']);
        $visible = Task::factory()->forOfficer($officer)->create(['due_date' => '2026-10-10']);
        Task::factory()->forOfficer($other)->create(['due_date' => '2026-10-10']);
        Task::factory()->forOfficer($officer)->create(['due_date' => '2026-11-01']);

        $this->actingAs($officer)->getJson(route('calendar.events', [
            'start' => '2026-10-01',
            'end' => '2026-11-01',
            'owner_id' => $other->id,
        ]))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $visible->id);
    }

    public function test_supervisor_can_filter_events_by_a_valid_officer_owner(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $owner = User::factory()->create(['role' => 'officer']);
        $other = User::factory()->create(['role' => 'officer']);
        $visible = Task::factory()->forOfficer($owner)->create(['due_date' => '2026-10-10']);
        Task::factory()->forOfficer($other)->create(['due_date' => '2026-10-10']);

        $this->actingAs($supervisor)->getJson(route('calendar.events', [
            'start' => '2026-10-01',
            'end' => '2026-11-01',
            'owner_id' => $owner->id,
        ]))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $visible->id);

        $this->actingAs($supervisor)->getJson(route('calendar.events', [
            'start' => '2026-10-01',
            'end' => '2026-11-01',
            'owner_id' => $supervisor->id,
        ]))->assertUnprocessable()->assertJsonValidationErrors('owner_id');
    }

    public function test_supervisor_receives_all_owners_tasks_with_owner_names(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $firstOwner = User::factory()->create(['role' => 'officer']);
        $secondOwner = User::factory()->create(['role' => 'officer']);
        $firstTask = Task::factory()->forOfficer($firstOwner)->create(['due_date' => '2026-10-10']);
        $secondTask = Task::factory()->forOfficer($secondOwner)->create(['due_date' => '2026-10-11']);

        $this->actingAs($supervisor)->getJson(route('calendar.events', $this->eventParameters()))
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $firstTask->id, 'title' => $firstTask->title.' — '.$firstOwner->name])
            ->assertJsonFragment(['id' => $secondTask->id, 'title' => $secondTask->title.' — '.$secondOwner->name]);
    }

    public function test_non_overdue_tasks_use_a_colour_for_each_status(): void
    {
        $officer = User::factory()->create(['role' => 'officer']);
        $toDo = Task::factory()->forOfficer($officer)->create([
            'due_date' => '2026-10-10', 'status' => TaskStatus::ToDo,
        ]);
        $inProgress = Task::factory()->forOfficer($officer)->create([
            'due_date' => '2026-10-11', 'status' => TaskStatus::InProgress,
        ]);
        $done = Task::factory()->forOfficer($officer)->create([
            'due_date' => '2026-10-12', 'status' => TaskStatus::Done,
        ]);

        $this->travelTo('2026-10-05');

        $this->actingAs($officer)->getJson(route('calendar.events', $this->eventParameters()))
            ->assertOk()
            ->assertJsonFragment(['id' => $toDo->id, 'color' => '#6b7280'])
            ->assertJsonFragment(['id' => $inProgress->id, 'color' => '#2563eb'])
            ->assertJsonFragment(['id' => $done->id, 'color' => '#16a34a']);
    }

    public function test_assigned_officer_receives_edit_url_for_visible_task(): void
    {
        $officer = User::factory()->create(['role' => 'officer']);
        $task = Task::factory()->forOfficer($officer)->create(['due_date' => '2026-10-10']);

        $this->actingAs($officer)->getJson(route('calendar.events', $this->eventParameters()))
            ->assertOk()
            ->assertJsonPath('0.url', route('tasks.edit', $task));
    }

    public function test_invalid_start_and_end_values_return_422_json(): void
    {
        $officer = User::factory()->create(['role' => 'officer']);

        $this->actingAs($officer)->getJson(route('calendar.events', [
            'start' => 'not-a-date', 'end' => '2026-11-01',
        ]))->assertUnprocessable()->assertJsonValidationErrors('start');

        $this->actingAs($officer)->getJson(route('calendar.events', [
            'start' => '2026-10-01', 'end' => 'not-a-date',
        ]))->assertUnprocessable()->assertJsonValidationErrors('end');

        $this->actingAs($officer)->getJson(route('calendar.events', [
            'start' => '2026-10-01', 'end' => '2026-10-01',
        ]))->assertUnprocessable()->assertJsonValidationErrors('end');
    }

    public function test_supervisor_cannot_filter_by_a_nonexistent_owner(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $existingOwner = User::factory()->create(['role' => 'officer']);
        $nonexistentOwnerId = User::query()->max('id') + 1;

        $this->actingAs($supervisor)->getJson(route('calendar.events', $this->eventParameters([
            'owner_id' => $nonexistentOwnerId,
        ])))->assertUnprocessable()->assertJsonValidationErrors('owner_id');
    }

    public function test_deleted_assigned_task_is_hidden_from_officer_events(): void
    {
        $officer = User::factory()->create(['role' => 'officer']);
        $task = Task::factory()->forOfficer($officer)->create(['due_date' => '2026-10-10']);
        $task->delete();

        $this->actingAs($officer)->getJson(route('calendar.events', $this->eventParameters()))
            ->assertOk()->assertJsonCount(0);
    }

    public function test_create_form_ignores_a_malformed_due_date(): void
    {
        $officer = User::factory()->create(['role' => 'officer']);

        $this->actingAs($officer)->get(route('tasks.create', ['due_date' => 'not-a-date']))
            ->assertOk()
            ->assertDontSee('value="not-a-date"', false);
    }

    public function test_supervisor_events_include_owner_colour_and_edit_url_but_not_deleted_tasks(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $owner = User::factory()->create(['role' => 'officer']);
        $task = Task::factory()->forOfficer($owner)->create([
            'title' => 'Fictional calendar task',
            'due_date' => now()->subDay(),
            'status' => TaskStatus::InProgress,
        ]);
        Task::factory()->forOfficer($owner)->create(['due_date' => now()])->delete();

        $response = $this->actingAs($supervisor)->getJson(route('calendar.events', [
            'start' => now()->subWeek()->toIso8601String(),
            'end' => now()->addWeek()->toIso8601String(),
        ]));

        $response->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.title', $task->title.' — '.$owner->name)
            ->assertJsonPath('0.color', '#dc2626')
            ->assertJsonPath('0.url', route('tasks.edit', $task));
    }
}
