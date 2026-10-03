@props(['color' => 'gray'])

@php
    $colors = [
        'gray' => 'bg-gray-100 text-gray-700 ring-gray-500/20',
        'green' => 'bg-green-50 text-green-700 ring-green-600/20',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'red' => 'bg-red-50 text-red-700 ring-red-600/20',
        'primary' => 'bg-primary-50 text-primary-700 ring-primary-600/20',
    ];
    $dots = ['gray' => 'bg-gray-400', 'green' => 'bg-green-500', 'amber' => 'bg-amber-500', 'red' => 'bg-red-500', 'primary' => 'bg-primary-500'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset ' . ($colors[$color] ?? $colors['gray'])]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dots[$color] ?? $dots['gray'] }}"></span>
    {{ $slot }}
</span>
