<?php

namespace Tests\Feature;

use App\Console\Commands\SendWeeklySummary;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WeeklySummaryNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WeeklySummaryCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_weekly_summary_is_sent_to_supervisors_and_not_to_officers(): void
    {
        Carbon::setTestNow('2026-10-12 08:00:00'); // Monday
        Notification::fake();

        $supervisor1 = User::factory()->create(['role' => 'supervisor', 'email' => 'sup1@example.test']);
        $supervisor2 = User::factory()->create(['role' => 'supervisor', 'email' => 'sup2@example.test']);
        $officer1 = User::factory()->create(['role' => 'officer', 'email' => 'off1@example.test']);
        $officer2 = User::factory()->create(['role' => 'officer', 'email' => 'off2@example.test']);

        Task::create([
            'title' => 'Sample Overdue Task',
            'description' => 'Detailed secret description that should not be in email.',
            'owner' => $officer1->name,
            'owner_id' => $officer1->id,
            'due_date' => today()->subDays(3),
            'priority' => TaskPriority::High,
            'status' => TaskStatus::InProgress,
            'progress_note' => 'Confidential internal progress note.',
            'progress_updated_at' => null,
        ]);

        $this->artisan('tasks:weekly-summary')->assertSuccessful();

        Notification::assertSentToTimes($supervisor1, WeeklySummaryNotification::class, 1);
        Notification::assertSentToTimes($supervisor2, WeeklySummaryNotification::class, 1);
        Notification::assertNotSentTo($officer1, WeeklySummaryNotification::class);
        Notification::assertNotSentTo($officer2, WeeklySummaryNotification::class);
    }

    public function test_weekly_summary_is_never_sent_twice_in_the_same_week(): void
    {
        Carbon::setTestNow('2026-10-12 08:00:00');
        Notification::fake();

        $supervisor = User::factory()->create(['role' => 'supervisor']);

        Task::create([
            'title' => 'Pending Task',
            'description' => 'Test task.',
            'owner' => 'Test Officer',
            'due_date' => today()->addDays(2),
            'priority' => TaskPriority::Medium,
            'status' => TaskStatus::ToDo,
        ]);

        // First run: sends 1 notification
        $this->artisan('tasks:weekly-summary')
            ->expectsOutputToContain('Sent weekly summary to 1 supervisor(s).')
            ->assertSuccessful();

        Notification::assertSentToTimes($supervisor, WeeklySummaryNotification::class, 1);

        // Immediate second run in the same week: duplicate prevented
        $this->artisan('tasks:weekly-summary')
            ->expectsOutputToContain('has already been sent')
            ->assertSuccessful();

        // Notification count remains 1
        Notification::assertSentToTimes($supervisor, WeeklySummaryNotification::class, 1);
    }

    public function test_notification_email_content_contains_figures_and_overdue_tasks_without_descriptions(): void
    {
        Carbon::setTestNow('2026-10-12 08:00:00');

        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $officer = User::factory()->create(['role' => 'officer', 'name' => 'Officer Sarah']);

        $descriptionSecret = 'TOP_SECRET_DESCRIPTION_TEXT';
        $noteSecret = 'CONFIDENTIAL_PROGRESS_NOTE_TEXT';

        $overdueTask = Task::create([
            'title' => 'Urgent Sanitation Enquiry',
            'description' => $descriptionSecret,
            'owner' => $officer->name,
            'owner_id' => $officer->id,
            'due_date' => today()->subDays(4),
            'priority' => TaskPriority::High,
            'status' => TaskStatus::InProgress,
            'progress_note' => $noteSecret,
            'progress_updated_at' => null,
        ]);

        $statistics = Task::getDashboardStatistics();
        $overdueTasks = Task::getOldestOverdueTasks(10);

        $notification = new WeeklySummaryNotification($statistics, $overdueTasks);
        $mail = $notification->toMail($supervisor);

        $this->assertSame('Weekly Task Monitoring Summary', $mail->subject);

        $lines = implode("\n", $mail->introLines);

        // Assert main figures are present
        $this->assertStringContainsString('Active tasks: 1', $lines);
        $this->assertStringContainsString('Overdue tasks: 1', $lines);
        $this->assertStringContainsString('Not updated this week: 1 (Target: 0)', $lines);
        $this->assertStringContainsString('Total tasks: 1', $lines);

        // Assert overdue task title, owner name, and due date are present
        $this->assertStringContainsString('Urgent Sanitation Enquiry', $lines);
        $this->assertStringContainsString('Officer Sarah', $lines);
        $this->assertStringContainsString($overdueTask->due_date->format('M j, Y'), $lines);

        // Assert dashboard action link is present
        $this->assertSame(route('supervisor.index'), $mail->actionUrl);

        // Critical: descriptions and notes must NOT be included in the email
        $this->assertStringNotContainsString($descriptionSecret, $lines);
        $this->assertStringNotContainsString($noteSecret, $lines);
    }

    public function test_weekly_summary_is_scheduled_for_mondays_at_0800(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $weeklySummaryEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'tasks:weekly-summary');
        });

        $this->assertNotNull($weeklySummaryEvent, 'tasks:weekly-summary is not registered in Schedule');
        $this->assertSame('0 8 * * 1', $weeklySummaryEvent->expression);
        $this->assertSame(config('app.timezone'), $weeklySummaryEvent->timezone);
        $this->assertTrue($weeklySummaryEvent->withoutOverlapping);
    }
}
