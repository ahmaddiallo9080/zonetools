@props(['initiales', 'size' => 'md'])

@php
    $sizes = ['sm' => 'h-8 w-8 text-xs', 'md' => 'h-10 w-10 text-sm', 'lg' => 'h-14 w-14 text-lg'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700 ' . ($sizes[$size] ?? $sizes['md'])]) }}>
    {{ $initiales }}
</span>
