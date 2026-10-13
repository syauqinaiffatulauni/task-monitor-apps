<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function officer(string $email = 'officer@example.test'): User
    {
        return User::factory()->create([
            'email' => $email,
            'role' => 'officer',
            'password' => 'password',
        ]);
    }

    private function supervisor(string $email = 'supervisor@example.test'): User
    {
        return User::factory()->create([
            'email' => $email,
            'role' => 'supervisor',
            'password' => 'password',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/supervisor')->assertRedirect('/login');
    }

    public function test_disallowed_role_officer_cannot_access_dashboard_and_gets_403(): void
    {
        $officer = $this->officer();

        $this->actingAs($officer)
            ->get('/supervisor')
            ->assertForbidden();

        // Ensure the officer does not see the Dashboard link in layout navigation
        $this->actingAs($officer)
            ->get('/my-tasks')
            ->assertOk()
            ->assertDontSee(route('supervisor.index'));
    }

    public function test_allowed_role_supervisor_can_access_dashboard_and_gets_200(): void
    {
        $supervisor = $this->supervisor();

        $this->actingAs($supervisor)
            ->get('/supervisor')
            ->assertOk()
            ->assertSee('Supervisor overview')
            ->assertSee(route('supervisor.index'));
    }

    public function test_each_role_lands_on_its_canvas_main_screen_after_login(): void
    {
        $officer = $this->officer('officer_landing@example.test');
        $supervisor = $this->supervisor('supervisor_landing@example.test');

        // Officer login lands on /my-tasks
        $this->post('/login', ['email' => $officer->email, 'password' => 'password'])
            ->assertRedirect(route('tasks.mine'));

        $this->post('/logout');

        // Supervisor login lands on /supervisor
        $this->post('/login', ['email' => $supervisor->email, 'password' => 'password'])
            ->assertRedirect(route('supervisor.index'));

        // Root '/' redirect matches canvas screens for each role
        $this->actingAs($officer)->get('/')->assertRedirect(route('tasks.mine'));
        $this->actingAs($supervisor)->get('/')->assertRedirect(route('supervisor.index'));
    }

    public function test_dashboard_loads_cleanly_when_database_has_no_tasks(): void
    {
        $supervisor = $this->supervisor();

        $this->assertDatabaseEmpty('tasks');

        $response = $this->actingAs($supervisor)->get('/supervisor');

        $response->assertOk();
        $response->assertSee('Supervisor overview');
        $response->assertSee('No tasks yet');
        $response->assertViewHas('statistics', [
            'total' => 0,
            'active' => 0,
            'open' => 0,
            'overdue' => 0,
            'due_next_7_days' => 0,
            'completed' => 0,
            'done' => 0,
            'stale' => 0,
        ]);
        $response->assertViewHas('chartLabels', []);
        $response->assertViewHas('chartValues', []);
        $response->assertViewHas('oldestOverdueTasks', fn ($tasks) => $tasks->isEmpty());
    }

    public function test_figures_are_correct_for_a_small_known_dataset(): void
    {
        $supervisor = $this->supervisor();
        $officerA = $this->officer('officera@example.test');
        $officerB = $this->officer('officerb@example.test');

        $today = today();
        $startOfWeek = now()->startOfWeek();

        // Task 1: Officer A - Overdue, InProgress, Stale (updated before start of week)
        $overdueTask = Task::create([
            'title' => 'Critical Overdue Inspection',
            'description' => 'Overdue task for officer A.',
            'owner' => $officerA->name,
            'owner_id' => $officerA->id,
            'due_date' => $today->copy()->subDays(2),
            'priority' => TaskPriority::High,
            'status' => TaskStatus::InProgress,
            'progress_note' => 'Old note from last week.',
            'progress_updated_at' => $startOfWeek->copy()->subDays(2),
        ]);

        // Task 2: Officer A - Due in 2 days (next 7 days), ToDo, Updated this week
        $dueSoonTask = Task::create([
            'title' => 'Upcoming Counter Renewal',
            'description' => 'Due in next 7 days.',
            'owner' => $officerA->name,
            'owner_id' => $officerA->id,
            'due_date' => $today->copy()->addDays(2),
            'priority' => TaskPriority::Medium,
            'status' => TaskStatus::ToDo,
            'progress_note' => 'Reviewed yesterday.',
            'progress_updated_at' => $startOfWeek->copy()->addHours(12),
        ]);

        // Task 3: Officer A - Due in 14 days, InProgress, Stale (null update)
        $dueLaterTask = Task::create([
            'title' => 'Long Term Security Audit',
            'description' => 'Due in two weeks.',
            'owner' => $officerA->name,
            'owner_id' => $officerA->id,
            'due_date' => $today->copy()->addDays(14),
            'priority' => TaskPriority::Low,
            'status' => TaskStatus::InProgress,
            'progress_note' => null,
            'progress_updated_at' => null,
        ]);

        // Task 4: Officer A - Completed task (past due, but done)
        $completedTask = Task::create([
            'title' => 'Finished Archive Export',
            'description' => 'Resolved task.',
            'owner' => $officerA->name,
            'owner_id' => $officerA->id,
            'due_date' => $today->copy()->subDays(5),
            'priority' => TaskPriority::Low,
            'status' => TaskStatus::Done,
            'progress_note' => 'Completed.',
            'progress_updated_at' => now()->subDay(),
        ]);

        // Task 5: Officer B - Due in 5 days (next 7 days), ToDo, Updated this week
        $officerBTask = Task::create([
            'title' => 'Draft Public Press Release',
            'description' => 'Due in next 7 days for officer B.',
            'owner' => $officerB->name,
            'owner_id' => $officerB->id,
            'due_date' => $today->copy()->addDays(5),
            'priority' => TaskPriority::High,
            'status' => TaskStatus::ToDo,
            'progress_note' => 'Drafting commenced.',
            'progress_updated_at' => $startOfWeek->copy()->addHours(6),
        ]);

        $response = $this->actingAs($supervisor)->get('/supervisor');

        $response->assertOk();

        // Exact expected figures
        $response->assertViewHas('statistics', [
            'total' => 5,
            'active' => 4,
            'open' => 4,
            'overdue' => 1,
            'due_next_7_days' => 2,
            'completed' => 1,
            'done' => 1,
            'stale' => 2,
        ]);

        // Check 10 oldest overdue table has the overdue task
        $response->assertViewHas('oldestOverdueTasks', function ($tasks) use ($overdueTask): bool {
            return $tasks->count() === 1 && $tasks->first()->id === $overdueTask->id;
        });

        // Ensure chart data has open tasks grouped per owner (Officer A = 3, Officer B = 1)
        $response->assertViewHas('chartLabels', [$officerA->name, $officerB->name]);
        $response->assertViewHas('chartValues', [3, 1]);

        // Check rendered HTML elements
        $response->assertSee('Critical Overdue Inspection');
        $response->assertSee(route('tasks.show', $overdueTask));
        $response->assertSee('Target: 0');
    }

    public function test_filtered_tasks_list_is_paginated_to_10_records_per_page(): void
    {
        $supervisor = $this->supervisor();
        $officer = $this->officer();

        // Create 25 tasks
        for ($i = 1; $i <= 25; $i++) {
            Task::create([
                'title' => "Task Number {$i}",
                'description' => "Description for task {$i}",
                'owner' => $officer->name,
                'owner_id' => $officer->id,
                'due_date' => today()->addDays($i),
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::ToDo,
            ]);
        }

        // Page 1
        $response = $this->actingAs($supervisor)->get('/supervisor');
        $response->assertOk();
        $response->assertViewHas('filteredTasks', function ($tasks): bool {
            return $tasks->count() === 10
                && $tasks->total() === 25
                && $tasks->perPage() === 10
                && $tasks->currentPage() === 1;
        });

        // Ensure pagination controls are rendered in the HTML
        $response->assertSee('page=2');

        // Page 2
        $page2Response = $this->actingAs($supervisor)->get('/supervisor?page=2');
        $page2Response->assertOk();
        $page2Response->assertViewHas('filteredTasks', function ($tasks): bool {
            return $tasks->count() === 10
                && $tasks->currentPage() === 2;
        });

        // Page 3 (remaining 5 tasks)
        $page3Response = $this->actingAs($supervisor)->get('/supervisor?page=3');
        $page3Response->assertOk();
        $page3Response->assertViewHas('filteredTasks', function ($tasks): bool {
            return $tasks->count() === 5
                && $tasks->currentPage() === 3;
        });
    }
}

