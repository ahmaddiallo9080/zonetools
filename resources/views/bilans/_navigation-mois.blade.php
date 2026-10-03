{{-- Sélecteur de mois. Variables : $mois (Carbon), $route ('bilans.index' ou 'bilans.show'), $site (pour bilans.show) --}}
@php
    $precedent = $mois->copy()->subMonthNoOverflow();
    $suivant = $mois->copy()->addMonthNoOverflow();
    $lien = fn ($m) => $route === 'bilans.index'
        ? route('bilans.index', ['mois' => $m->format('Y-m')])
        : route('bilans.show', [$m->format('Y-m'), $site]);
@endphp
<div class="no-print flex items-center gap-2">
    <a href="{{ $lien($precedent) }}" class="rounded-lg border border-gray-300 bg-white p-2 text-gray-600 hover:bg-gray-50" title="Mois précédent">
        <x-icon name="chevron-down" class="h-4 w-4 rotate-90" />
    </a>
    @if ($route === 'bilans.index')
        <form method="GET" action="{{ route('bilans.index') }}">
            <input type="month" name="mois" value="{{ $mois->format('Y-m') }}" max="{{ now()->format('Y-m') }}" onchange="this.form.submit()"
                   class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
        </form>
    @else
        <span class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-900">{{ ucfirst($mois->translatedFormat('F Y')) }}</span>
    @endif
    @if ($suivant->lessThanOrEqualTo(now()->startOfMonth()))
        <a href="{{ $lien($suivant) }}" class="rounded-lg border border-gray-300 bg-white p-2 text-gray-600 hover:bg-gray-50" title="Mois suivant">
            <x-icon name="chevron-down" class="h-4 w-4 -rotate-90" />
        </a>
    @endif
</div>
