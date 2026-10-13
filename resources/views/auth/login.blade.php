@extends('layouts.app')

@section('title', __('Sign in').' · Taskboard')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
            @if(session('status'))<div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
            <h1 class="text-2xl font-bold">{{ __('Sign in') }}</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('Use your work account to continue.') }}</p>
            <form action="{{ route('login.store') }}" method="POST" class="mt-7 space-y-5">
                @csrf
                <div><label for="email" class="mb-2 block text-sm font-semibold">{{ __('Email') }}</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 dark:border-slate-700 dark:bg-slate-950">@error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="password" class="mb-2 block text-sm font-semibold">{{ __('Password') }}</label><input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 dark:border-slate-700 dark:bg-slate-950">@error('password')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <label class="flex items-center gap-2 text-sm"><input name="remember" type="checkbox" value="1" class="rounded border-slate-300"> {{ __('Remember me') }}</label>
                <button class="w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-700">{{ __('Sign in') }}</button>
            </form>
            <p class="mt-6 text-xs text-slate-500">{{ __('Demo password') }}: <span class="font-mono">password</span></p>
        </div>
    </div>
@endsection
