<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDeadlineReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function task(User $owner, int $daysFromToday, string $status = 'to do'): Task
    {
        return Task::create([
            'title' => "Task due in {$daysFromToday} days",
            'description' => 'A fictional reminder test task.',
            'owner' => $owner->name,
            'owner_id' => $owner->id,
            'due_date' => today()->addDays($daysFromToday),
            'priority' => 'medium',
            'status' => $status,
        ]);
    }

    public function test_reminders_are_sent_at_selected_stages_only(): void
    {
        Carbon::setTestNow('2026-10-12 08:00:00');
        Notification::fake();
        $officer = User::factory()->create(['role' => 'officer']);
        $threeDays = $this->task($officer, 3);
        $oneDay = $this->task($officer, 1);
        $overdue = $this->task($officer, -1);
        $this->task($officer, 2);

        $this->artisan('tasks:send-reminders')->assertSuccessful();

        Notification::assertSentToTimes($officer, TaskDeadlineReminder::class, 3);
        $this->assertDatabaseHas('task_reminders', ['task_id' => $threeDays->id, 'stage' => 'three_days_before']);
        $this->assertDatabaseHas('task_reminders', ['task_id' => $oneDay->id, 'stage' => 'one_day_before']);
        $this->assertDatabaseHas('task_reminders', ['task_id' => $overdue->id, 'stage' => 'overdue']);
    }

    public function test_the_same_reminder_is_never_sent_twice(): void
    {
        Carbon::setTestNow('2026-10-12 08:00:00');
        Notification::fake();
        $officer = User::factory()->create(['role' => 'officer']);
        $this->task($officer, 1);

        $this->artisan('tasks:send-reminders')->assertSuccessful();
        $this->artisan('tasks:send-reminders')->assertSuccessful();

        Notification::assertSentToTimes($officer, TaskDeadlineReminder::class, 1);
        $this->assertDatabaseCount('task_reminders', 1);
    }

    public function test_done_tasks_never_receive_reminders(): void
    {
        Carbon::setTestNow('2026-10-12 08:00:00');
        Notification::fake();
        $officer = User::factory()->create(['role' => 'officer']);
        $this->task($officer, -2, 'done');

        $this->artisan('tasks:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertDatabaseCount('task_reminders', 0);
    }

    public function test_log_mail_driver_is_configured(): void
    {
        $this->assertSame('log', config('mail.mailers.log.transport'));
    }
}
