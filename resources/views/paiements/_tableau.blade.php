{{-- Tableau de paiements. Variables : $paiements, $compact (bool) --}}
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 border-t border-gray-100 text-sm">
        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
            <tr>
                <th class="px-4 py-3">Paiement</th>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Bénéficiaire</th>
                <th class="px-4 py-3">Mode</th>
                <th class="px-4 py-3">Période</th>
                <th class="px-4 py-3 text-right">Montant</th>
                @unless ($compact ?? false)<th class="px-4 py-3 text-right">Actions</th>@endunless
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @foreach ($paiements as $paiement)
                <tr class="hover:bg-gray-50">
                    <td class="whitespace-nowrap px-4 py-3"><a href="{{ route('paiements.show', $paiement) }}" class="font-mono text-xs font-semibold text-gray-900 hover:text-primary-600">{{ $paiement->code }}</a></td>
                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="font-medium text-gray-900">{{ $paiement->beneficiaire?->nom_complet }}</span>
                        <span class="block text-xs text-gray-500">{{ $paiement->type_label }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $paiement->mode_label }}@if ($paiement->reference)<span class="block font-mono text-xs text-gray-400">{{ $paiement->reference }}</span>@endif</td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $paiement->periode_label ?? '—' }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-green-700">{{ $paiement->montant_affiche }}</td>
                    @unless ($compact ?? false)
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <x-table-actions
                                :show="route('paiements.show', $paiement)"
                                :edit="route('paiements.edit', $paiement)"
                                :destroy="route('paiements.destroy', $paiement)"
                                :name="'supprimer-paiement-'.$paiement->id"
                                :message="'Le paiement '.$paiement->code.' de '.$paiement->montant_affiche.' sera supprimé.'" />
                        </td>
                    @endunless
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
