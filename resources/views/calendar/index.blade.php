@extends('layouts.app')

@section('title', __('Calendar').' · Taskboard')

@section('content')
    <div class="mb-8">
        <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-indigo-600">{{ __('Workspace') }}</p>
        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Task calendar') }}</h1>
        <p class="mt-3 text-slate-500 dark:text-slate-400">{{ __('View task deadlines by date.') }}</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
        <div id="task-calendar"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const calendar = new FullCalendar.Calendar(document.getElementById('task-calendar'), {
                initialView: 'dayGridMonth',
                firstDay: 1,
                height: 'auto',
                events: @js(route('calendar.events')),
                eventClick(info) {
                    if (!info.event.url) {
                        info.jsEvent.preventDefault();
                    }
                },
            });

            calendar.render();
        });
    </script>
@endsection
