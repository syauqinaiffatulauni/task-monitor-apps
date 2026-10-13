<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'view' => ['nullable', 'in:total,active,overdue,completed,stale,due_next_7_days'],
        ]);
        $selectedView = $validated['view'] ?? 'total';

        $statistics = Task::getDashboardStatistics();

        $chartData = Task::query()
            ->open()
            ->leftJoin('users', 'tasks.owner_id', '=', 'users.id')
            ->selectRaw('COALESCE(users.name, tasks.owner) as owner_label, count(*) as count')
            ->groupBy('owner_label')
            ->orderByDesc('count')
            ->pluck('count', 'owner_label');

        $chartLabels = $chartData->keys()->values()->all();
        $chartValues = $chartData->values()->all();

        $oldestOverdueTasks = Task::getOldestOverdueTasks(10);

        $filteredTasksQuery = match ($selectedView) {
            'active' => Task::query()->open(),
            'overdue' => Task::query()->overdue(),
            'completed' => Task::query()->where('status', TaskStatus::Done->value),
            'stale' => Task::query()->stale(),
            'due_next_7_days' => Task::query()->dueWithinDays(7),
            default => Task::query(),
        };

        $filteredTasks = $filteredTasksQuery
            ->with('assignedOfficer')
            ->orderBy('due_date')
            ->paginate(10)
            ->withQueryString();

        return view('supervisor.overview', [
            'statistics' => $statistics,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
            'oldestOverdueTasks' => $oldestOverdueTasks,
            'selectedView' => $selectedView,
            'filteredTasks' => $filteredTasks,
        ]);
    }
}
