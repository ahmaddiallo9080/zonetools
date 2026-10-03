@props(['route' => null, 'active' => null, 'icon' => null])

@php
    $exists = $route && Route::has($route);
    $isActive = $active ?? ($exists && request()->routeIs(str_replace('.index', '', $route) . '*'));
    $classes = $isActive
        ? 'bg-white/15 text-white font-semibold'
        : 'text-primary-100 hover:bg-white/10 hover:text-white';
@endphp

@if ($exists)
    <a href="{{ route($route) }}" {{ $attributes->merge(['class' => "group flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition $classes"]) }}>
        @if ($icon)<x-icon :name="$icon" class="h-5 w-5 shrink-0" />@endif
        <span class="truncate">{{ $slot }}</span>
    </a>
@else
    <span {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-primary-200/60 cursor-not-allowed']) }} title="Module à venir">
        @if ($icon)<x-icon :name="$icon" class="h-5 w-5 shrink-0" />@endif
        <span class="truncate">{{ $slot }}</span>
        <span class="ms-auto rounded bg-white/10 px-1.5 py-0.5 text-[10px] uppercase tracking-wide">bientôt</span>
    </span>
@endif
