@props(['column', 'tri', 'sens'])

@php
    $actif = $tri === $column;
    $prochainSens = $actif && $sens === 'asc' ? 'desc' : 'asc';
    $url = request()->fullUrlWithQuery(['tri' => $column, 'sens' => $prochainSens, 'page' => null]);
@endphp

<a href="{{ $url }}" class="group inline-flex items-center gap-1 hover:text-gray-900 {{ $actif ? 'text-gray-900' : '' }}">
    {{ $slot }}
    <span class="text-[10px] {{ $actif ? 'text-primary-600' : 'text-gray-300 group-hover:text-gray-400' }}">
        {{ $actif && $sens === 'desc' ? '▼' : '▲' }}
    </span>
</a>
