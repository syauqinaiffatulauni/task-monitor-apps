<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="scheme-light dark:scheme-dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Taskboard')</title>
    <script>
        (() => {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', savedTheme === 'dark' || (!savedTheme && prefersDark));
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-900 antialiased transition-colors dark:bg-slate-950 dark:text-slate-100">
    <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-10">
            <a href="{{ auth()->check() ? route('tasks.index') : route('login') }}" class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-indigo-600 text-white shadow-sm shadow-indigo-200">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </span>
                <span class="text-lg font-bold tracking-tight">Taskboard</span>
            </a>
            <div class="flex items-center gap-2">
                @auth
                <nav aria-label="Main navigation" class="flex items-center gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
                    @if(!auth()->user()->isSupervisor())<a href="{{ route('tasks.mine') }}" class="rounded-lg px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('tasks.mine') ? 'bg-white text-indigo-700 shadow-sm dark:bg-slate-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300' }}">{{ __('My Tasks') }}</a>@endif
                    <a href="{{ route('tasks.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('tasks.index') ? 'bg-white text-indigo-700 shadow-sm dark:bg-slate-700 dark:text-indigo-300' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">{{ __('All Tasks') }}</a>
                    <a href="{{ route('calendar') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold transition sm:block {{ request()->routeIs('calendar*') ? 'bg-white text-indigo-700 shadow-sm dark:bg-slate-700 dark:text-indigo-300' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">{{ __('Calendar') }}</a>
                    <a href="{{ route('tasks.create') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold transition sm:block {{ request()->routeIs('tasks.create') ? 'bg-white text-indigo-700 shadow-sm dark:bg-slate-700 dark:text-indigo-300' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">{{ __('Add task') }}</a>
                    @if(auth()->user()->isSupervisor())<a href="{{ route('supervisor.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('supervisor.*') ? 'bg-white text-indigo-700 shadow-sm dark:bg-slate-700 dark:text-indigo-300' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">{{ __('Dashboard') }}</a>@endif
                </nav>
                @endauth
                <button type="button" data-theme-toggle class="grid size-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white" aria-label="Toggle colour theme" aria-pressed="false">
                    <svg data-moon-icon class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                    <svg data-sun-icon class="hidden size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg>
                </button>
                @auth<form action="{{ route('locale.update') }}" method="POST" class="hidden rounded-lg border border-slate-200 bg-white p-0.5 dark:border-slate-700 dark:bg-slate-800 sm:flex" aria-label="Language">
                    @csrf
                    <button type="submit" name="locale" value="en" class="rounded-md px-2 py-1.5 text-xs font-bold {{ app()->isLocale('en') ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:text-slate-900' }}" aria-label="English">EN</button>
                    <button type="submit" name="locale" value="ms" class="rounded-md px-2 py-1.5 text-xs font-bold {{ app()->isLocale('ms') ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:text-slate-900' }}" aria-label="Bahasa Melayu">BM</button>
                </form>@endauth
                @auth<div class="hidden text-right xl:block"><p class="text-xs font-bold">{{ auth()->user()->name }}</p><p class="text-xs capitalize text-slate-500">{{ __(Str::headline(auth()->user()->role)) }}</p></div><form action="{{ route('logout') }}" method="POST">@csrf<button class="rounded-lg px-2 py-2 text-xs font-bold text-slate-500 hover:text-red-600">{{ __('Log out') }}</button></form>@endauth
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl flex-1 px-5 py-10 sm:px-8 lg:px-10 lg:py-14">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10">
            <p>&copy; {{ now()->year }} Taskboard</p>
            <p>{{ __('Simple task management for your team.') }}</p>
        </div>
    </footer>
</body>
</html>
