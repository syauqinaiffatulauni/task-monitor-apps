@extends('layouts.app')

@section('title', __('Calendar').' · Taskboard')

@section('content')
    <div class="mb-8">
        <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-indigo-600">{{ __('Workspace') }}</p>
        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Task calendar') }}</h1>
        <p class="mt-3 text-slate-500 dark:text-slate-400">{{ __('View task deadlines by date.') }}</p>
    </div>

    <div class="mb-4 flex flex-wrap gap-x-5 gap-y-2 text-xs font-medium text-slate-600 dark:text-slate-300" aria-label="{{ __('Calendar colour legend') }}">
        <span class="inline-flex items-center gap-2"><span class="size-3 rounded-full bg-slate-500"></span>{{ __('To Do') }}</span>
        <span class="inline-flex items-center gap-2"><span class="size-3 rounded-full bg-blue-600"></span>{{ __('In Progress') }}</span>
        <span class="inline-flex items-center gap-2"><span class="size-3 rounded-full bg-emerald-600"></span>{{ __('Done') }}</span>
        <span class="inline-flex items-center gap-2"><span class="size-3 rounded-full bg-red-600"></span>{{ __('Overdue') }}</span>
    </div>

    @can('viewAll', \App\Models\Task::class)
        <div class="mb-4 max-w-xs">
            <label for="calendar-owner" class="mb-2 block text-sm font-semibold">{{ __('Owner') }}</label>
            <select id="calendar-owner" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">{{ __('All owners') }}</option>
                @foreach ($owners as $owner)
                    <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                @endforeach
            </select>
        </div>
    @endcan

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
        <div id="task-calendar"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const eventsUrl = @json(route('calendar.events'));
            const createTaskUrl = @json(route('tasks.create'));
            const calendarElement = document.getElementById('task-calendar');
            const ownerSelect = document.getElementById('calendar-owner');

            const calendar = new FullCalendar.Calendar(calendarElement, {
                initialView: 'dayGridMonth',
                firstDay: 1,
                height: 'auto',
                navLinks: true,
                selectable: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek',
                },
                buttonText: {
                    today: 'Today',
                    month: 'Month',
                    week: 'Week',
                    list: 'List',
                },
                events: {
                    url: eventsUrl,
                    extraParams: function () {
                        return ownerSelect && ownerSelect.value
                            ? {owner_id: ownerSelect.value}
                            : {};
                    },
                },
                eventClick: function (info) {
                    info.jsEvent.preventDefault();

                    if (info.event.url) {
                        window.location.assign(info.event.url);
                    }
                },
                dateClick: function (info) {
                    const url = new URL(createTaskUrl, window.location.origin);
                    url.searchParams.set('due_date', info.dateStr);
                    window.location.assign(url.toString());
                },
            });

            calendar.render();

            ownerSelect?.addEventListener('change', function () {
                calendar.refetchEvents();
            });
        });
    </script>
@endsection
