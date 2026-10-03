@props(['lignes', 'couleur' => 'bg-primary-600', 'suffixe' => null, 'vide' => 'Aucune donnée sur la période.'])
{{--
    Barres horizontales (une seule série), triées par l'appelant.
    $lignes : [['label' => 'Hotspot Kipé', 'valeur' => 123000, 'affiche' => '123 000 GNF', 'detail' => '45 tickets', 'lien' => url], ...]
--}}
@php $max = max(1, collect($lignes)->max('valeur')); @endphp

@if (empty($lignes))
    <p class="py-8 text-center text-sm text-gray-500">{{ $vide }}</p>
@else
    <ul class="space-y-3">
        @foreach ($lignes as $ligne)
            <li class="group">
                <div class="flex items-baseline justify-between gap-3 text-sm">
                    @if (! empty($ligne['lien']))
                        <a href="{{ $ligne['lien'] }}" class="truncate font-medium text-gray-900 hover:text-primary-600">{{ $ligne['label'] }}</a>
                    @else
                        <span class="truncate font-medium text-gray-900">{{ $ligne['label'] }}</span>
                    @endif
                    <span class="shrink-0 tabular-nums font-semibold text-gray-900">{{ $ligne['affiche'] }}</span>
                </div>
                <div class="mt-1.5 flex items-center gap-2">
                    <div class="h-2 flex-1 rounded-full bg-gray-100">
                        <div class="h-2 rounded-full {{ $couleur }} transition-opacity group-hover:opacity-80"
                             style="width: {{ max($ligne['valeur'] > 0 ? 1 : 0, $ligne['valeur'] / $max * 100) }}%"></div>
                    </div>
                    @if (! empty($ligne['detail']))
                        <span class="w-28 shrink-0 text-right text-xs text-gray-500">{{ $ligne['detail'] }}</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
