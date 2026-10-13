@extends('layouts.app')

@section('title', __('Supervisor overview').' · Taskboard')

@section('content')
    @php
        $labels = [
            'total' => __('Total tasks'),
            'active' => __('Active tasks'),
            'overdue' => __('Overdue tasks'),
            'due_next_7_days' => __('Due in next 7 days'),
            'completed' => __('Completed tasks'),
            'stale' => __('Not updated this week'),
        ];
    @endphp

    <div>
        <h1 class="text-3xl font-bold">{{ __('Supervisor overview') }}</h1>
        <p class="mt-2 text-slate-500 dark:text-slate-400">{{ __('Review team progress and tasks needing follow-up.') }}</p>
    </div>

    {{-- Metric summary tiles --}}
    <nav aria-label="{{ __('Task statistics') }}" class="mt-7 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ($labels as $key => $label)
            <a href="{{ route('supervisor.index', ['view' => $key]) }}" class="flex flex-col justify-between rounded-2xl border p-5 transition hover:-translate-y-0.5 hover:shadow-md {{ $selectedView === $key ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100 dark:bg-indigo-950/40 dark:ring-indigo-900' : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' }}" @if($selectedView === $key) aria-current="true" @endif>
                <div>
                    <p class="text-xs font-medium {{ $selectedView === $key ? 'text-indigo-700 dark:text-indigo-300' : 'text-slate-500 dark:text-slate-400' }}">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ $statistics[$key] }}</p>
                </div>
                @if ($key === 'stale')
                    <span class="mt-3 inline-block w-fit rounded-md bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">{{ __('Target: 0') }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    @if ($selectedView === 'total')
        {{-- Chart.js: Open tasks per owner bar chart --}}
        <section class="mt-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <div class="mb-4">
                <h2 class="text-lg font-bold">{{ __('Open tasks per owner') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Workload distribution across team members.') }}</p>
            </div>
            @if (empty($chartLabels) || array_sum($chartValues) === 0)
                <p class="py-12 text-center text-sm text-slate-500">{{ __('No tasks yet') }}</p>
            @else
                <div class="relative h-64 w-full sm:h-72">
                    <canvas id="workloadChart" aria-label="{{ __('Bar chart of open tasks per owner') }}"></canvas>
                </div>
            @endif
        </section>

        {{-- 10 Oldest Overdue Tasks Table --}}
        <section class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-6">
                <h2 class="font-bold">{{ __('10 oldest overdue tasks') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Incomplete tasks with the earliest past deadlines.') }}</p>
            </div>
            @if ($oldestOverdueTasks->isEmpty())
                <p class="px-6 py-12 text-center text-sm text-slate-500">{{ __('No tasks yet') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/70">
                            <tr>
                                <th class="px-6 py-3.5">{{ __('Task') }}</th>
                                <th class="px-4 py-3.5">{{ __('Owner') }}</th>
                                <th class="px-4 py-3.5">{{ __('Priority') }}</th>
                                <th class="px-4 py-3.5">{{ __('Status') }}</th>
                                <th class="px-6 py-3.5 text-right">{{ __('Due date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($oldestOverdueTasks as $task)
                                <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                                    <td class="px-6 py-4 font-semibold">
                                        @can('view', $task)
                                            <a href="{{ route('tasks.show', $task) }}" class="text-indigo-600 hover:text-indigo-800 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300">
                                                {{ $task->title }}
                                            </a>
                                        @else
                                            <span class="text-slate-700 dark:text-slate-300">{{ $task->title }}</span>
                                        @endcan
                                    </td>
                                    <td class="px-4 py-4 text-slate-500">{{ $task->assignedOfficer?->name ?? $task->owner }}</td>
                                    <td class="px-4 py-4"><x-task-badge type="priority" :value="$task->priority" /></td>
                                    <td class="px-4 py-4"><x-task-badge type="status" :value="$task->status" /></td>
                                    <td class="px-6 py-4 text-right font-semibold text-red-600 dark:text-red-400">{{ $task->due_date->format('M j, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    {{-- Filtered task category list --}}
    <section class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-6">
            <h2 class="font-bold">{{ $labels[$selectedView] ?? __('Tasks') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ trans_choice(':count task total|:count tasks total', $filteredTasks->total(), ['count' => $filteredTasks->total()]) }}</p>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($filteredTasks as $task)
                @php($overdue = $task->isOverdue())
                <a href="{{ route('tasks.show', $task) }}" class="flex flex-col gap-3 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-800/60 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="min-w-0">
                        <p class="truncate font-semibold {{ $task->status === \App\Enums\TaskStatus::Done ? 'text-slate-400 line-through' : '' }}">{{ $task->title }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $task->assignedOfficer?->name ?? $task->owner }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-task-badge type="priority" :value="$task->priority" />
                        <x-task-badge type="status" :value="$task->status" />
                        <span class="w-24 text-right text-sm {{ $overdue ? 'font-semibold text-red-600 dark:text-red-400' : 'text-slate-500' }}">{{ $task->due_date->format('M j, Y') }}</span>
                    </div>
                </a>
            @empty
                <p class="px-6 py-12 text-center text-sm text-slate-500">{{ __('No tasks yet') }}</p>
            @endforelse
        </div>
        @if ($filteredTasks->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-800 sm:px-6">
                {{ $filteredTasks->links() }}
            </div>
        @endif
    </section>

    @if (!empty($chartLabels) && array_sum($chartValues) > 0)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" integrity="sha384-vsrfeLOOY6KuIYKDlmVH5UiBmgIdB1oEf7p01YgWHuqmOHfZr374+odEv96n9tNC" crossorigin="anonymous"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const chartLabels = @json($chartLabels);
                const chartValues = @json($chartValues);
                const ctx = document.getElementById('workloadChart');
                if (!ctx) return;

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: '{{ __('Open tasks') }}',
                            data: chartValues,
                            backgroundColor: '#6366f1',
                            borderRadius: 8,
                            maxBarThickness: 44,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            });
        </script>
    @endif
@endsection
