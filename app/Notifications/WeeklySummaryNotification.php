<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class WeeklySummaryNotification extends Notification
{
    use Queueable;

    /**
     * @param array<string, int> $statistics
     * @param Collection<\App\Models\Task> $overdueTasks
     */
    public function __construct(
        public array $statistics,
        public Collection $overdueTasks
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Weekly Task Monitoring Summary')
            ->greeting("Hello {$notifiable->name},")
            ->line('Here is the weekly task monitoring summary for your team:')
            ->line("• Active tasks: {$this->statistics['active']}")
            ->line("• Overdue tasks: {$this->statistics['overdue']}")
            ->line("• Due in next 7 days: {$this->statistics['due_next_7_days']}")
            ->line("• Completed tasks: {$this->statistics['completed']}")
            ->line("• Not updated this week: {$this->statistics['stale']} (Target: 0)")
            ->line("• Total tasks: {$this->statistics['total']}");

        if ($this->overdueTasks->isNotEmpty()) {
            $message->line('Oldest overdue tasks needing follow-up:');
            foreach ($this->overdueTasks->take(10) as $task) {
                $owner = $task->assignedOfficer?->name ?? $task->owner;
                $due = $task->due_date->format('M j, Y');
                $message->line("• {$task->title} — {$owner} (Due: {$due})");
            }
        } else {
            $message->line('No overdue tasks currently.');
        }

        return $message
            ->action('View Supervisor Dashboard', route('supervisor.index'))
            ->line('Review team progress and follow up on pending items.');
    }
}
