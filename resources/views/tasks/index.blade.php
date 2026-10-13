@extends('layouts.app')

@section('title', 'Tasks · Taskboard')

@section('content')
    <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-indigo-600">{{ __('Workspace') }}</p>
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $assignedOnly ? __('My Tasks') : __('All Tasks') }}</h1>
            <p class="mt-3 max-w-2xl text-slate-500 dark:text-slate-400">{{ __('Task page introduction') }}</p>
        </div>
        @can('create', \App\Models\Task::class)
            <a href="{{ route('tasks.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                {{ __('Add task') }}
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300" role="status">
            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ $assignedOnly ? route('tasks.mine') : route('tasks.index') }}" method="GET" class="mb-7 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900" role="search">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(240px,1fr)_200px_200px_auto_auto]">
            <div>
                <label for="q" class="sr-only">{{ __('Search tasks') }}</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] ?? $filters['search'] ?? '' }}" maxlength="100" placeholder="{{ __('Search placeholder') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:ring-indigo-950">
            </div>
            <div>
                <label for="priority-filter" class="sr-only">{{ __('Priority') }}</label>
                <select id="priority-filter" name="priority" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="">{{ __('All priorities') }}</option>
                    @foreach ([\App\Enums\TaskPriority::High, \App\Enums\TaskPriority::Medium, \App\Enums\TaskPriority::Low] as $priority)
                        <option value="{{ $priority->value }}" @selected(($filters['priority'] ?? '') === $priority->value || ($resolvedPriority?->value === $priority->value))>{{ __($priority->label()) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status-filter" class="sr-only">{{ __('Status') }}</label>
                <select id="status-filter" name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (\App\Enums\TaskStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value || ($resolvedStatus?->value === $status->value))>{{ __($status->label()) }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-medium dark:border-slate-700">
                <input name="overdue" type="checkbox" value="1" @checked($isOverdueFilter) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                {{ __('Overdue only') }}
            </label>
            <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-slate-700 dark:bg-indigo-600 dark:hover:bg-indigo-500">{{ __('Filter') }}</button>
        </div>
        @if ($isFiltered)
            <a href="{{ $assignedOnly ? route('tasks.mine') : route('tasks.index') }}" class="mt-3 inline-block text-sm font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">{{ __('Clear filters') }}</a>
        @endif
    </form>

    <section class="mb-7" aria-labelledby="statistics-heading">
        <h2 id="statistics-heading" class="sr-only">{{ __('Overview') }}</h2>
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Total tasks') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $statistics['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-4 dark:border-blue-900 dark:bg-blue-950/40 sm:p-5">
                <p class="text-sm font-medium text-blue-700 dark:text-blue-300">{{ __('Active tasks') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-blue-900 dark:text-blue-100">{{ $statistics['active'] }}</p>
            </div>
            <div class="rounded-2xl border border-red-100 bg-red-50/70 p-4 dark:border-red-900 dark:bg-red-950/40 sm:p-5">
                <p class="text-sm font-medium text-red-700 dark:text-red-300">{{ __('Overdue tasks') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-red-900 dark:text-red-100">{{ $statistics['overdue'] }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 dark:border-emerald-900 dark:bg-emerald-950/40 sm:p-5">
                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ __('Completed tasks') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-emerald-900 dark:text-emerald-100">{{ $statistics['completed'] }}</p>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:[&_thead]:bg-slate-800/70 dark:[&_tbody]:divide-slate-800">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-5 dark:border-slate-800 sm:px-6">
            <div>
                <h2 class="font-bold">{{ __('All tasks') }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ trans_choice(':count task total|:count tasks total', $tasks->total(), ['count' => $tasks->total()]) }}
                    @if ($isFiltered)
                        <span class="font-medium text-indigo-600 dark:text-indigo-400">({{ __('filtered') }})</span>
                    @endif
                </p>
            </div>
            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">{{ __(':count completed', ['count' => $statistics['completed']]) }}</span>
        </div>

        @if ($tasks->isEmpty())
            <div class="px-6 py-20 text-center">
                <h3 class="font-bold">{{ $isFiltered ? __('No tasks match these filters.') : __('No tasks yet') }}</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $isFiltered ? __('Try changing or clearing your filters.') : __('Add your first task to get started.') }}</p>
            </div>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500"><tr><th class="px-6 py-4">{{ __('Task') }}</th><th class="px-4 py-4">{{ __('Owner') }}</th><th class="px-4 py-4">{{ __('Due date') }}</th><th class="px-4 py-4">{{ __('Priority') }}</th><th class="px-6 py-4">{{ __('Status') }}</th><th class="px-6 py-4 text-right">{{ __('Actions') }}</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach ($tasks as $task)
                            @php($overdue = $task->isOverdue())
                            <tr class="transition {{ $overdue ? 'bg-red-50/80 hover:bg-red-50 dark:bg-red-950/30 dark:hover:bg-red-950/40' : 'hover:bg-slate-50/70 dark:hover:bg-slate-800/60' }}">
                                <td class="max-w-md px-6 py-5"><a href="{{ route('tasks.show', $task) }}" class="font-semibold hover:text-indigo-600 {{ $task->status === \App\Enums\TaskStatus::Done ? 'text-slate-400 line-through decoration-2 dark:text-slate-500' : 'text-slate-900 dark:text-slate-100' }}">{{ $task->title }}</a><p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $task->description }}</p></td>
                                <td class="whitespace-nowrap px-4 py-5 text-slate-600 dark:text-slate-300">{{ $task->owner }}</td>
                                <td class="whitespace-nowrap px-4 py-5 {{ $overdue ? 'font-semibold text-red-700 dark:text-red-300' : 'text-slate-600 dark:text-slate-300' }}">{{ $task->due_date->format('M j, Y') }} @if($overdue)<span class="ml-1 block text-xs">{{ __('Overdue') }}</span>@endif</td>
                                <td class="px-4 py-5"><x-task-badge type="priority" :value="$task->priority" /></td>
                                <td class="px-6 py-5">
                                    <x-task-badge type="status" :value="$task->status" />
                                    @can('changeStatus', $task)
                                        <form action="{{ route('tasks.status.update', $task) }}" method="POST" class="mt-2 flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <label for="status-{{ $task->id }}" class="sr-only">{{ __('Update status') }}</label>
                                            <select id="status-{{ $task->id }}" name="status" class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-950">
                                                @foreach (\App\Enums\TaskStatus::cases() as $status)
                                                    <option value="{{ $status->value }}" @selected($task->status === $status)>{{ __($status->label()) }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="rounded-lg bg-indigo-600 px-2.5 py-1.5 text-xs font-bold text-white hover:bg-indigo-700">{{ __('Save status') }}</button>
                                        </form>
                                    @endcan
                                </td>
                                <td class="whitespace-nowrap px-6 py-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @can('update', $task)
                                            <a href="{{ route('tasks.edit', $task) }}" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                                {{ __('Edit') }}
                                            </a>
                                        @endcan
                                        @can('delete', $task)
                                            <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm(@js(__('Delete this task?')))" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-bold text-red-600 transition hover:bg-red-50 dark:border-red-900/60 dark:text-red-400 dark:hover:bg-red-950/40">
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
                @foreach ($tasks as $task)
                    @php($overdue = $task->isOverdue())
                    <article class="p-5 {{ $overdue ? 'border-l-4 border-red-500 bg-red-50/70 dark:bg-red-950/30' : '' }}">
                        <div class="flex items-start justify-between gap-3"><a href="{{ route('tasks.show', $task) }}" class="font-semibold {{ $task->status === \App\Enums\TaskStatus::Done ? 'text-slate-400 line-through decoration-2' : '' }}">{{ $task->title }}</a><x-task-badge type="priority" :value="$task->priority" /></div>
                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $task->description }}</p>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><p class="text-xs text-slate-400">{{ __('Owner') }}</p><p class="mt-1 text-slate-600">{{ $task->owner }}</p></div><div><p class="text-xs text-slate-400">{{ __('Due date') }}</p><p class="mt-1 {{ $overdue ? 'font-semibold text-red-700' : 'text-slate-600' }}">{{ $task->due_date->format('M j, Y') }} @if($overdue)<span class="block text-xs">{{ __('Overdue') }}</span>@endif</p></div></div>
                        <div class="mt-4">
                            <x-task-badge type="status" :value="$task->status" />
                            @can('changeStatus', $task)
                                <form action="{{ route('tasks.status.update', $task) }}" method="POST" class="mt-3 flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <label for="mobile-status-{{ $task->id }}" class="sr-only">{{ __('Update status') }}</label>
                                    <select id="mobile-status-{{ $task->id }}" name="status" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                        @foreach (\App\Enums\TaskStatus::cases() as $status)
                                            <option value="{{ $status->value }}" @selected($task->status === $status)>{{ __($status->label()) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white hover:bg-indigo-700">{{ __('Save status') }}</button>
                                </form>
                            @endcan
                        </div>
                        <div class="mt-4 flex items-center justify-end gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                            @can('update', $task)
                                <a href="{{ route('tasks.edit', $task) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                    {{ __('Edit') }}
                                </a>
                            @endcan
                            @can('delete', $task)
                                <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm(@js(__('Delete this task?')))" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-600 transition hover:bg-red-50 dark:border-red-900/60 dark:text-red-400 dark:hover:bg-red-950/40">
                                        {{ __('Delete') }}
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @if ($tasks->hasPages())
        <div class="mt-6">
            {{ $tasks->links() }}
        </div>
    @endif
@endsection
