<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\FilterTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(FilterTasksRequest $request): View
    {
        return $this->taskList($request);
    }

    public function mine(FilterTasksRequest $request): View
    {
        return $this->taskList($request, true);
    }

    private function taskList(FilterTasksRequest $request, bool $assignedOnly = false): View
    {
        $filters = $request->validated();
        $user = $request->user();
        $query = $assignedOnly
            ? $user->tasks()->with('assignedOfficer')
            : Task::query()->with('assignedOfficer');

        $searchTerm = $filters['q'] ?? $filters['search'] ?? null;
        $hasSearch = $searchTerm !== null && trim($searchTerm) !== '';
        if ($hasSearch) {
            $query->where('title', 'like', '%'.trim($searchTerm).'%');
        }

        $rawPriority = $filters['priority'] ?? null;
        $resolvedPriority = $this->resolvePriority($rawPriority);
        if ($resolvedPriority !== null) {
            $query->where('priority', $resolvedPriority->value);
        }

        $rawStatus = $filters['status'] ?? null;
        $resolvedStatus = $this->resolveStatus($rawStatus);
        if ($resolvedStatus !== null) {
            $query->where('status', $resolvedStatus->value);
        }

        $isOverdueFilter = in_array($filters['overdue'] ?? null, ['1', 1, true, 'true', 'on'], true);
        if ($isOverdueFilter) {
            $query->where('status', '!=', TaskStatus::Done->value)
                ->whereDate('due_date', '<', today());
        }

        $isFiltered = $hasSearch || ($resolvedStatus !== null) || ($resolvedPriority !== null) || $isOverdueFilter;

        $allTasks = $query
            ->orderBy('due_date')
            ->get()
            ->sortBy(function (Task $task): int {
                if ($task->status === TaskStatus::Done) {
                    return 100;
                }

                return match ($task->priority) {
                    TaskPriority::High => 1,
                    TaskPriority::Medium => 2,
                    TaskPriority::Low => 3,
                };
            })
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $tasks = new LengthAwarePaginator(
            $allTasks->forPage($page, 5)->values(),
            $allTasks->count(),
            5,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $filters['q'] = $searchTerm ?? '';

        return view('tasks.index', [
            'tasks' => $tasks,
            'statistics' => [
                'total' => $allTasks->count(),
                'active' => $allTasks->filter(fn (Task $task): bool => $task->status !== TaskStatus::Done)->count(),
                'overdue' => $allTasks->filter(fn (Task $task): bool => $task->isOverdue())->count(),
                'completed' => $allTasks->filter(fn (Task $task): bool => $task->status === TaskStatus::Done)->count(),
            ],
            'filters' => $filters,
            'isFiltered' => $isFiltered,
            'resolvedStatus' => $resolvedStatus,
            'resolvedPriority' => $resolvedPriority,
            'isOverdueFilter' => $isOverdueFilter,
            'assignedOnly' => $assignedOnly,
        ]);
    }

    private function resolveStatus(?string $status): ?TaskStatus
    {
        if ($status === null || trim($status) === '') {
            return null;
        }

        $normalized = strtolower(trim(str_replace(['_', '-'], ' ', $status)));

        return match ($normalized) {
            'not started', 'to do', 'todo' => TaskStatus::ToDo,
            'in progress' => TaskStatus::InProgress,
            'complete', 'completed', 'done' => TaskStatus::Done,
            default => null,
        };
    }

    private function resolvePriority(?string $priority): ?TaskPriority
    {
        if ($priority === null || trim($priority) === '') {
            return null;
        }

        $normalized = strtolower(trim($priority));

        return match ($normalized) {
            'low' => TaskPriority::Low,
            'medium' => TaskPriority::Medium,
            'high' => TaskPriority::High,
            default => null,
        };
    }

    public function create(Request $request): View
    {
        $requestedDueDate = $request->query('due_date');
        $parsedDueDate = is_string($requestedDueDate)
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $requestedDueDate)
            : false;
        $prefillDueDate = $parsedDueDate !== false && $parsedDueDate->format('Y-m-d') === $requestedDueDate
            ? $requestedDueDate
            : null;

        return view('tasks.create', [
            'officers' => User::query()->where('role', 'officer')->orderBy('name')->get(),
            'prefillDueDate' => $prefillDueDate,
        ]);
    }

    public function show(Request $request, Task $task): View
    {
        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task): View
    {
        return view('tasks.edit', [
            'task' => $task,
            'officers' => User::query()->where('role', 'officer')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->user()->isSupervisor() && isset($validated['owner_id'])) {
            $officer = User::query()->where('role', 'officer')->findOrFail($validated['owner_id']);
            $task->update($validated + ['owner' => $officer->name, 'progress_updated_at' => now()]);

            return to_route('tasks.show', $task)->with('success', __('Task updated successfully.'));
        }

        // For staff members: prevent changing owner_id or user_id
        unset($validated['owner_id'], $validated['user_id']);
        $ownTask = $request->user()->tasks()->findOrFail($task->id);
        $ownTask->update($validated + [
            'owner_id' => $ownTask->owner_id,
            'owner' => $ownTask->owner,
            'progress_updated_at' => now(),
        ]);

        return to_route('tasks.mine')->with('success', __('Task updated successfully.'));
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $officer = User::query()->where('role', 'officer')->findOrFail($validated['owner_id']);

        Task::create($validated + ['owner' => $officer->name]);

        return to_route('tasks.index')->with('success', __('Task added successfully.'));
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        if ($request->user()->isSupervisor()) {
            $task->delete();

            return to_route('tasks.index')->with('success', __('Task archived successfully.'));
        }

        $ownTask = $request->user()->tasks()->findOrFail($task->id);
        $ownTask->delete();

        return to_route('tasks.mine')->with('success', __('Task archived successfully.'));
    }
}
