<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Notifications\TaskDeadlineReminder;
use Illuminate\Console\Command;

class SendTaskReminders extends Command
{
    protected $signature = 'tasks:send-reminders';
    protected $description = 'Send due-date reminders for incomplete tasks';

    public function handle(): int
    {
        $today = today();
        $sent = 0;

        Task::query()
            ->with('assignedOfficer')
            ->where('status', '!=', TaskStatus::Done->value)
            ->whereNotNull('owner_id')
            ->get()
            ->each(function (Task $task) use ($today, &$sent): void {
                $stage = match (true) {
                    $task->due_date->isSameDay($today->copy()->addDays(3)) => 'three_days_before',
                    $task->due_date->isSameDay($today->copy()->addDay()) => 'one_day_before',
                    $task->due_date->lt($today) => 'overdue',
                    default => null,
                };

                if ($stage === null || $task->assignedOfficer === null) return;

                $reminder = $task->reminders()->firstOrCreate(['stage' => $stage]);
                if (! $reminder->wasRecentlyCreated) return;

                try {
                    $task->assignedOfficer->notify(new TaskDeadlineReminder($task, $stage));
                    $reminder->update(['sent_at' => now()]);
                    $sent++;
                } catch (\Throwable $exception) {
                    $reminder->delete();
                    throw $exception;
                }
            });

        $this->info("Sent {$sent} task reminder(s).");

        return self::SUCCESS;
    }
}
