@extends('layouts.app')

@section('title', $task->title.' · Taskboard')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('tasks.index') }}" class="text-sm font-semibold text-slate-500 hover:text-indigo-600">&larr; {{ __('Back to tasks') }}</a>
        @if(session('success'))<div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
        <article class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4"><div><h1 class="text-2xl font-bold">{{ $task->title }}</h1><p class="mt-3 text-slate-500 dark:text-slate-400">{{ $task->description }}</p></div><div class="flex gap-2"><x-task-badge type="priority" :value="$task->priority"/><x-task-badge type="status" :value="$task->status"/></div></div>
            <dl class="mt-7 grid gap-5 border-y border-slate-200 py-6 dark:border-slate-800 sm:grid-cols-3">
                <div><dt class="text-xs text-slate-500">{{ __('Owner') }}</dt><dd class="mt-1 font-semibold">{{ $task->assignedOfficer?->name ?? $task->owner }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Due date') }}</dt><dd class="mt-1 font-semibold">{{ $task->due_date->format('M j, Y') }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Last updated') }}</dt><dd class="mt-1 font-semibold">{{ $task->progress_updated_at?->diffForHumans() ?? __('No update yet') }}</dd></div>
            </dl>
            <div class="mt-6"><h2 class="font-bold">{{ __('Latest progress note') }}</h2><p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $task->progress_note ?? __('No progress note yet.') }}</p></div>

            <div class="mt-7 flex flex-wrap gap-3">
                @can('update', $task)
                    <a href="{{ route('tasks.edit', $task) }}" class="inline-flex rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white">{{ __('Edit task') }}</a>
                @endcan
                @can('changeDueDate', $task)
                    <a href="{{ route('tasks.due-date.edit', $task) }}" class="inline-flex rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold">{{ __('Change due date') }}</a>
                @endcan
            </div>

            @can('updateProgress', $task)
                <form action="{{ route('tasks.progress.update', $task) }}" method="POST" class="mt-8 space-y-4 border-t border-slate-200 pt-6 dark:border-slate-800">
                    @csrf @method('PUT')
                    <h2 class="font-bold">{{ __('Update progress') }}</h2>
                    <div><label for="status" class="mb-2 block text-sm font-semibold">{{ __('Status') }}</label><select id="status" name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">@foreach(\App\Enums\TaskStatus::cases() as $status)<option value="{{ $status->value }}" @selected(old('status', $task->status->value) === $status->value)>{{ __($status->label()) }}</option>@endforeach</select>@error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label for="progress_note" class="mb-2 block text-sm font-semibold">{{ __('Progress note') }}</label><textarea id="progress_note" name="progress_note" rows="4" maxlength="2000" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">{{ old('progress_note', $task->progress_note) }}</textarea>@error('progress_note')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white">{{ __('Save progress') }}</button>
                </form>
            @endcan

            @can('delete', $task)
                <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="mt-6 border-t border-slate-200 pt-6 dark:border-slate-800" onsubmit="return confirm(@js(__('Archive this task?')))" >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-xl border border-red-300 px-4 py-2.5 text-sm font-bold text-red-700 transition hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/40">{{ __('Archive task') }}</button>
                </form>
            @endcan
        </article>
    </div>
@endsection
