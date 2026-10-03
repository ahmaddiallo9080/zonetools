{{-- Derniers lots (fiches site / agent). Variables : $lots, $titre, $lienTous, $lienNouveau --}}
<div class="card overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4">
        <h3 class="font-semibold text-gray-900">{{ $titre }}</h3>
        <div class="flex items-center gap-4 text-sm font-medium">
            @if ($lots->isNotEmpty())<a href="{{ $lienTous }}" class="text-gray-500 hover:text-primary-600">Tout voir</a>@endif
            @isset($lienNouveau)<a href="{{ $lienNouveau }}" class="text-primary-600 hover:text-primary-700">+ Nouveau lot</a>@endisset
        </div>
    </div>
    @if ($lots->isEmpty())
        <p class="border-t border-gray-100 px-6 py-6 text-sm text-gray-500">Aucun lot pour le moment.</p>
    @else
        <table class="min-w-full divide-y divide-gray-100 border-t border-gray-100 text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach ($lots as $lot)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-6 py-3"><a href="{{ route('lots.show', $lot) }}" class="font-mono text-xs font-semibold text-gray-900 hover:text-primary-600">{{ $lot->code }}</a></td>
                        <td class="whitespace-nowrap px-6 py-3 text-gray-600">{{ $lot->date_remise->format('d/m/Y') }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $lot->agent?->nom_complet }}</td>
                        <td class="whitespace-nowrap px-6 py-3 text-right">{{ $lot->quantite_totale }} tickets</td>
                        <td class="whitespace-nowrap px-6 py-3 text-right font-semibold">{{ $lot->montant_affiche }}</td>
                        <td class="px-6 py-3 text-right"><x-badge :color="$lot->statut_color">{{ $lot->statut_label }}</x-badge></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
