@extends('layouts.app')
@section('title', __('Edit task').' · Taskboard')
@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ auth()->user()->isSupervisor() ? route('tasks.show', $task) : route('tasks.mine') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">&larr; {{ auth()->user()->isSupervisor() ? __('Back to task') : __('Back to tasks') }}</a>
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h1 class="text-2xl font-bold">{{ __('Edit task') }}</h1>
        <form action="{{ route('tasks.update', $task) }}" method="POST" class="mt-7 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-2 block text-sm font-semibold" for="title">{{ __('Title') }}</label>
                <input class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950" id="title" name="title" value="{{ old('title', $task->title) }}" required>
                @error('title')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold" for="description">{{ __('Description') }}</label>
                <textarea class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950" id="description" name="description" rows="4" required>{{ old('description', $task->description) }}</textarea>
                @error('description')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold" for="owner_id">{{ __('Owner') }}</label>
                    @if(auth()->user()->isSupervisor())
                        <select class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950" id="owner_id" name="owner_id" required>
                            @foreach($officers as $officer)
                                <option value="{{ $officer->id }}" @selected(old('owner_id', $task->owner_id) == $officer->id)>{{ $officer->name }}</option>
                            @endforeach
                        </select>
                        @error('owner_id')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    @else
                        <div class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-950/50 dark:text-slate-300">
                            {{ $task->owner }}
                        </div>
                    @endif
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold" for="due_date">{{ __('Due date') }}</label>
                    <input class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950" id="due_date" name="due_date" type="date" value="{{ old('due_date', $task->due_date->toDateString()) }}" required>
                    @error('due_date')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold" for="priority">{{ __('Priority') }}</label>
                    <select class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950" id="priority" name="priority">
                        @foreach([\App\Enums\TaskPriority::High, \App\Enums\TaskPriority::Medium, \App\Enums\TaskPriority::Low] as $value)
                            <option value="{{ $value->value }}" @selected(old('priority', $task->priority->value) === $value->value)>{{ __($value->label()) }}</option>
                        @endforeach
                    </select>
                    @error('priority')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold" for="status">{{ __('Status') }}</label>
                    <select class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950" id="status" name="status">
                        @foreach(\App\Enums\TaskStatus::cases() as $value)
                            <option value="{{ $value->value }}" @selected(old('status', $task->status->value) === $value->value)>{{ __($value->label()) }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold" for="progress_note">{{ __('Progress note') }}</label>
                <textarea class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950" id="progress_note" name="progress_note" rows="3">{{ old('progress_note', $task->progress_note) }}</textarea>
                @error('progress_note')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-700">{{ __('Save changes') }}</button>
        </form>
    </div>
</div>
@endsection
