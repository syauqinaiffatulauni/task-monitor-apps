<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Notifications\WeeklySummaryNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendWeeklySummary extends Command
{
    protected $signature = 'tasks:weekly-summary {--force : Force sending even if already sent this week}';

    protected $description = 'Send weekly task monitoring summary to supervisors';

    public function handle(): int
    {
        $weekKey = 'weekly-summary-sent-'.now()->startOfWeek()->toDateString();

        if (! $this->option('force') && Cache::has($weekKey)) {
            $this->info("Weekly summary for {$weekKey} has already been sent.");
            return self::SUCCESS;
        }

        $supervisors = User::query()
            ->where('role', 'supervisor')
            ->get();

        if ($supervisors->isEmpty()) {
            $this->info('No supervisors found to receive the weekly summary.');
            return self::SUCCESS;
        }

        $statistics = Task::getDashboardStatistics();
        $overdueTasks = Task::getOldestOverdueTasks(10);

        $sent = 0;

        foreach ($supervisors as $supervisor) {
            $userKey = "{$weekKey}:user:{$supervisor->id}";

            if (! $this->option('force') && Cache::has($userKey)) {
                continue;
            }

            try {
                $supervisor->notify(new WeeklySummaryNotification($statistics, $overdueTasks));
                Cache::put($userKey, now(), now()->addWeeks(2));
                $sent++;
            } catch (\Throwable $exception) {
                $this->error("Failed to send weekly summary to {$supervisor->email}: {$exception->getMessage()}");
                throw $exception;
            }
        }

        Cache::put($weekKey, now(), now()->addWeeks(2));

        $this->info("Sent weekly summary to {$sent} supervisor(s).");

        return self::SUCCESS;
    }
}
