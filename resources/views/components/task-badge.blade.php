@props(['type', 'value'])

@php
    $enum = $value instanceof \BackedEnum ? $value : null;
    $rawValue = $enum?->value ?? $value;
    $label = method_exists($enum, 'label') ? $enum->label() : Str::headline($rawValue);
    $classes = match ([$type, $rawValue]) {
        ['priority', 'high'] => 'bg-red-50 text-red-700 ring-red-600/10 dark:bg-red-950/60 dark:text-red-300 dark:ring-red-400/20',
        ['priority', 'medium'] => 'bg-amber-50 text-amber-700 ring-amber-600/10 dark:bg-amber-950/60 dark:text-amber-300 dark:ring-amber-400/20',
        ['priority', 'low'] => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-400/20',
        ['status', 'done'] => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-400/20',
        ['status', 'in progress'] => 'bg-blue-50 text-blue-700 ring-blue-600/10 dark:bg-blue-950/60 dark:text-blue-300 dark:ring-blue-400/20',
        default => 'bg-slate-100 text-slate-600 ring-slate-500/10 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-400/20',
    };
@endphp

<span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $classes }}">{{ __($label) }}</span>
