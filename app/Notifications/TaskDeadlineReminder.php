<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDeadlineReminder extends Notification
{
    use Queueable;

    public function __construct(public Task $task, public string $stage) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $timing = match ($this->stage) {
            'three_days_before' => 'is due in three days',
            'one_day_before' => 'is due tomorrow',
            'overdue' => 'is overdue',
        };

        return (new MailMessage)
            ->subject("Task reminder: {$this->task->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your task '{$this->task->title}' {$timing}.")
            ->line("Due date: {$this->task->due_date->format('F j, Y')}")
            ->action('View task', route('tasks.show', $this->task))
            ->line('Please review the task and update its progress.');
    }
}
