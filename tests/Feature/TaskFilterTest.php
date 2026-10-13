<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFilterTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'officer'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function task(User $owner, array $attributes = []): Task
    {
        return Task::create($attributes + [
            'title' => 'Sample monitoring task',
            'description' => 'A fictional task.',
            'owner' => $owner->name,
            'owner_id' => $owner->id,
            'due_date' => today()->addWeek(),
            'priority' => 'high',
            'status' => 'to do',
        ]);
    }

    public function test_status_filter_shows_matching_tasks_and_excludes_others(): void
    {
        $officer = $this->user('officer');

        $notStarted1 = $this->task($officer, ['title' => 'Task Not Started 1', 'status' => 'to do']);
        $notStarted2 = $this->task($officer, ['title' => 'Task Not Started 2', 'status' => 'to do']);
        $notStarted3 = $this->task($officer, ['title' => 'Task Not Started 3', 'status' => 'to do']);
        $complete1 = $this->task($officer, ['title' => 'Task Complete 1', 'status' => 'done']);
        $complete2 = $this->task($officer, ['title' => 'Task Complete 2', 'status' => 'done']);

        $response = $this->actingAs($officer)->get('/my-tasks?status=Complete');
        $response->assertOk();
        $response->assertSee($complete1->title);
        $response->assertSee($complete2->title);
        $response->assertDontSee($notStarted1->title);
        $response->assertDontSee($notStarted2->title);
        $response->assertDontSee($notStarted3->title);
    }

    public function test_priority_filter_shows_matching_tasks_and_excludes_others(): void
    {
        $officer = $this->user('officer');

        $highTask = $this->task($officer, ['title' => 'High Priority Assignment', 'priority' => 'high']);
        $mediumTask = $this->task($officer, ['title' => 'Medium Priority Assignment', 'priority' => 'medium']);
        $lowTask = $this->task($officer, ['title' => 'Low Priority Assignment', 'priority' => 'low']);

        $response = $this->actingAs($officer)->get('/my-tasks?priority=High');
        $response->assertOk();
        $response->assertSee($highTask->title);
        $response->assertDontSee($mediumTask->title);
        $response->assertDontSee($lowTask->title);
    }

    public function test_filters_can_be_combined(): void
    {
        $officer = $this->user('officer');

        $matching = $this->task($officer, [
            'title' => 'Bridge Inspection Alpha',
            'priority' => 'high',
            'status' => 'in progress',
            'due_date' => today()->addDays(2),
        ]);

        $differentPriority = $this->task($officer, [
            'title' => 'Bridge Inspection Beta',
            'priority' => 'low',
            'status' => 'in progress',
            'due_date' => today()->addDays(2),
        ]);

        $differentStatus = $this->task($officer, [
            'title' => 'Bridge Inspection Gamma',
            'priority' => 'high',
            'status' => 'done',
            'due_date' => today()->addDays(2),
        ]);

        $differentTitle = $this->task($officer, [
            'title' => 'Road Paving Delta',
            'priority' => 'high',
            'status' => 'in progress',
            'due_date' => today()->addDays(2),
        ]);

        $response = $this->actingAs($officer)->get('/my-tasks?q=Bridge&priority=High&status=in progress');
        $response->assertOk();
        $response->assertSee($matching->title);
        $response->assertDontSee($differentPriority->title);
        $response->assertDontSee($differentStatus->title);
        $response->assertDontSee($differentTitle->title);
    }

    public function test_overdue_filter_excludes_complete_tasks(): void
    {
        $officer = $this->user('officer');

        $incompleteOverdue = $this->task($officer, [
            'title' => 'Incomplete Overdue Task',
            'status' => 'in progress',
            'due_date' => today()->subDay(),
        ]);

        $completePastDue = $this->task($officer, [
            'title' => 'Complete Past Due Task',
            'status' => 'done',
            'due_date' => today()->subDay(),
        ]);

        $response = $this->actingAs($officer)->get('/my-tasks?overdue=1');
        $response->assertOk();
        $response->assertSee($incompleteOverdue->title);
        $response->assertDontSee($completePastDue->title);
    }

    public function test_task_due_today_is_not_overdue(): void
    {
        $officer = $this->user('officer');

        $dueToday = $this->task($officer, [
            'title' => 'Task Due Today',
            'status' => 'to do',
            'due_date' => today(),
        ]);

        $dueYesterday = $this->task($officer, [
            'title' => 'Task Due Yesterday',
            'status' => 'to do',
            'due_date' => today()->subDay(),
        ]);

        $response = $this->actingAs($officer)->get('/my-tasks?overdue=1');
        $response->assertOk();
        $response->assertSee($dueYesterday->title);
        $response->assertDontSee($dueToday->title);
    }

    public function test_overdue_filter_agrees_with_task_is_overdue(): void
    {
        $officer = $this->user('officer');

        $overdueTask = $this->task($officer, [
            'title' => 'Overdue Agreement Task',
            'status' => 'in progress',
            'due_date' => today()->subDays(2),
        ]);

        $todayTask = $this->task($officer, [
            'title' => 'Today Agreement Task',
            'status' => 'in progress',
            'due_date' => today(),
        ]);

        $futureTask = $this->task($officer, [
            'title' => 'Future Agreement Task',
            'status' => 'to do',
            'due_date' => today()->addDay(),
        ]);

        $completeTask = $this->task($officer, [
            'title' => 'Complete Agreement Task',
            'status' => 'done',
            'due_date' => today()->subDays(2),
        ]);

        // Model method verification
        $this->assertTrue($overdueTask->isOverdue());
        $this->assertFalse($todayTask->isOverdue());
        $this->assertFalse($futureTask->isOverdue());
        $this->assertFalse($completeTask->isOverdue());

        // HTTP filter agreement verification
        $response = $this->actingAs($officer)->get('/my-tasks?overdue=1');
        $response->assertOk();
        $response->assertSee($overdueTask->title);
        $response->assertDontSee($todayTask->title);
        $response->assertDontSee($futureTask->title);
        $response->assertDontSee($completeTask->title);
    }

    public function test_title_search_matches_text_in_title(): void
    {
        $officer = $this->user('officer');

        $auditTask = $this->task($officer, ['title' => 'Quarterly Financial Audit']);
        $reviewTask = $this->task($officer, ['title' => 'Annual Staff Review']);

        $response = $this->actingAs($officer)->get('/my-tasks?q=Audit');
        $response->assertOk();
        $response->assertSee($auditTask->title);
        $response->assertDontSee($reviewTask->title);
    }

    public function test_title_search_handles_literal_percent_sign(): void
    {
        $officer = $this->user('officer');

        $fiftyPercentTask = $this->task($officer, ['title' => 'Completed 50% milestone']);
        $hundredPercentTask = $this->task($officer, ['title' => 'Completed 100% milestone']);
        $standardTask = $this->task($officer, ['title' => 'Completed full milestone']);

        $response = $this->actingAs($officer)->get('/my-tasks?q=50%25');
        $response->assertOk();
        $response->assertSee($fiftyPercentTask->title);
        $response->assertDontSee($hundredPercentTask->title);
        $response->assertDontSee($standardTask->title);
    }

    public function test_another_users_tasks_never_appear_for_any_filter(): void
    {
        $officer = $this->user('officer');
        $otherOfficer = $this->user('officer');

        $otherTask = $this->task($otherOfficer, [
            'title' => 'Budget review for Q4',
            'status' => 'to do',
            'priority' => 'high',
            'due_date' => today()->subDay(),
        ]);

        $myTask = $this->task($officer, [
            'title' => 'Amina Operations Plan',
            'status' => 'to do',
            'priority' => 'high',
            'due_date' => today()->subDay(),
        ]);

        // Query with title filter matching other officer's task
        $this->actingAs($officer)->get('/my-tasks?q=Budget')
            ->assertOk()
            ->assertDontSee($otherTask->title);

        // Query with status filter
        $this->actingAs($officer)->get('/my-tasks?status=to do')
            ->assertOk()
            ->assertSee($myTask->title)
            ->assertDontSee($otherTask->title);

        // Query with priority filter
        $this->actingAs($officer)->get('/my-tasks?priority=high')
            ->assertOk()
            ->assertSee($myTask->title)
            ->assertDontSee($otherTask->title);

        // Query with overdue filter
        $this->actingAs($officer)->get('/my-tasks?overdue=1')
            ->assertOk()
            ->assertSee($myTask->title)
            ->assertDontSee($otherTask->title);
    }

    public function test_unknown_status_or_priority_is_ignored(): void
    {
        $officer = $this->user('officer');
        $task = $this->task($officer, ['title' => 'Standard Active Task']);

        $response = $this->actingAs($officer)->get('/my-tasks?status=UnknownStatus&priority=UnknownPriority');
        $response->assertOk();
        $response->assertSee($task->title);
    }

    public function test_an_array_value_does_not_break_the_page(): void
    {
        $officer = $this->user('officer');
        $task = $this->task($officer, ['title' => 'Resilient Task']);

        $response = $this->actingAs($officer)->get('/my-tasks?q[]=test&status[]=to do&priority[]=high&overdue[]=1');

        $this->assertLessThan(500, $response->getStatusCode());
    }

    public function test_form_keeps_the_chosen_values(): void
    {
        $officer = $this->user('officer');
        $this->task($officer, [
            'title' => 'Special Project Report',
            'priority' => 'high',
            'status' => 'to do',
        ]);

        $response = $this->actingAs($officer)->get('/my-tasks?q=Special&priority=high&status=to do&overdue=1');
        $response->assertOk();
        $response->assertSee('value="Special"', false);
        $response->assertSee('value="high" selected', false);
        $response->assertSee('value="to do" selected', false);
        $response->assertSee('(filtered)');
    }
}
