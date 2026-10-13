<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Task::class);

        return view('calendar', [
            'owners' => Gate::allows('viewAll', Task::class)
                ? User::query()->where('role', 'officer')->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Task::class);

        $canViewAll = Gate::forUser($request->user())->allows('viewAll', Task::class);
        $rules = [
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ];

        if ($canViewAll) {
            $rules['owner_id'] = ['nullable', Rule::exists('users', 'id')->where('role', 'officer')];
        }

        $validator = Validator::make($request->query(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('The calendar date range is invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $timezone = config('app.timezone');
        $start = CarbonImmutable::parse($validated['start'])->setTimezone($timezone);
        $end = CarbonImmutable::parse($validated['end'])->setTimezone($timezone);
        $user = $request->user();

        $tasks = Task::query()
            ->with('assignedOfficer')
            ->visibleTo($user)
            ->when($canViewAll && isset($validated['owner_id']), fn ($query) => $query->where('owner_id', $validated['owner_id']))
            ->whereDate('due_date', '>=', $start->toDateString())
            ->whereDate('due_date', '<', $end->toDateString())
            ->orderBy('due_date')
            ->get();

        return response()->json($tasks->map(function (Task $task) use ($user): array {
            $title = $user->isSupervisor()
                ? $task->title.' — '.($task->assignedOfficer?->name ?? $task->owner)
                : $task->title;

            return [
                'id' => $task->id,
                'title' => $title,
                'start' => $task->due_date->toDateString(),
                'allDay' => true,
                'color' => $this->eventColor($task),
                'url' => Gate::forUser($user)->allows('update', $task)
                    ? route('tasks.edit', $task)
                    : null,
            ];
        })->values());
    }

    private function eventColor(Task $task): string
    {
        if ($task->isOverdue()) {
            return '#dc2626';
        }

        return match ($task->status) {
            TaskStatus::ToDo => '#6b7280',
            TaskStatus::InProgress => '#2563eb',
            TaskStatus::Done => '#16a34a',
        };
    }
}
