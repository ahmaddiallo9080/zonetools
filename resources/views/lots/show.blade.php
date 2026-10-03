@php use App\Support\Format; @endphp
<x-app-layout>
    <x-slot name="title">Lot {{ $lot->code }}</x-slot>
    <x-slot name="header">Lot {{ $lot->code }}</x-slot>
    <x-slot name="actions">
        @if ($lot->estModifiable())
            <a href="{{ route('lots.edit', $lot) }}"><x-primary-button type="button">Modifier</x-primary-button></a>
        @endif
    </x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('lots.index') }}" class="hover:text-primary-600">Lots</a> / <span class="text-gray-700">{{ $lot->code }}</span>
    </nav>

    @if ($lot->estEnRetard())
        <div class="mb-6 flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="alert" class="h-5 w-5 shrink-0" />
            La date de fin prévue ({{ $lot->date_fin_prevue->format('d/m/Y') }}) est dépassée et aucun rapport n'a encore été saisi.
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- En-tête --}}
            <div class="card p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-600 text-white">
                            <x-icon name="ticket" class="h-6 w-6" />
                        </span>
                        <div>
                            <h2 class="font-mono text-lg font-semibold text-gray-900">{{ $lot->code }}</h2>
                            <p class="text-sm text-gray-500">Remis le {{ $lot->date_remise->translatedFormat('d F Y') }}</p>
                        </div>
                    </div>
                    <x-badge :color="$lot->statut_color">{{ $lot->statut_label }}</x-badge>
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
                    <div class="rounded-lg bg-gray-50 p-4">
                        <dt class="text-gray-500">Site</dt>
                        <dd class="mt-1 font-semibold">
                            @if ($lot->site)<a href="{{ route('sites.show', $lot->site) }}" class="hover:text-primary-600">{{ $lot->site->nom }}</a>@endif
                        </dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4">
                        <dt class="text-gray-500">Agent</dt>
                        <dd class="mt-1 font-semibold">
                            @if ($lot->agent)<a href="{{ route('agents.show', $lot->agent) }}" class="hover:text-primary-600">{{ $lot->agent->nom_complet }}</a>@endif
                        </dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4">
                        <dt class="text-gray-500">Superviseur</dt>
                        <dd class="mt-1 font-semibold">
                            @if ($lot->superviseur)
                                <a href="{{ route('superviseurs.show', $lot->superviseur) }}" class="hover:text-primary-600">{{ $lot->superviseur->nom_complet }}</a>
                            @else
                                <span class="text-gray-400">Aucun</span>
                            @endif
                        </dd>
                    </div>
                </dl>

                <p class="mt-4 text-sm text-gray-500">Fin prévue : <span class="font-medium text-gray-700">{{ $lot->date_fin_prevue?->format('d/m/Y') ?? 'non définie' }}</span></p>

                @if ($lot->notes)
                    <div class="mt-4 whitespace-pre-line rounded-lg bg-gray-50 p-4 text-sm text-gray-700">{{ $lot->notes }}</div>
                @endif
            </div>

            {{-- Contenu --}}
            <div class="card overflow-hidden">
                <div class="px-6 py-4">
                    <h3 class="font-semibold text-gray-900">Contenu du lot</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-100 border-t border-gray-100 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Forfait</th>
                            <th class="px-6 py-3 text-right">Quantité</th>
                            <th class="px-6 py-3 text-right">Prix unitaire</th>
                            <th class="px-6 py-3 text-right">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($lot->lignes as $ligne)
                            <tr>
                                <td class="px-6 py-3">
                                    <x-badge :color="$ligne->forfait->couleur">{{ $ligne->forfait->nom }}</x-badge>
                                    <span class="ms-2 text-xs text-gray-500">{{ $ligne->forfait->duree_label }}</span>
                                </td>
                                <td class="px-6 py-3 text-right font-medium">{{ number_format($ligne->quantite, 0, ',', ' ') }}</td>
                                <td class="whitespace-nowrap px-6 py-3 text-right text-gray-600">{{ Format::gnf($ligne->prix_unitaire) }}</td>
                                <td class="whitespace-nowrap px-6 py-3 text-right font-semibold">{{ Format::gnf($ligne->montant) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-gray-200 bg-gray-50">
                        <tr>
                            <td class="px-6 py-3 font-semibold">Total</td>
                            <td class="px-6 py-3 text-right font-semibold">{{ number_format($lot->quantite_totale, 0, ',', ' ') }} tickets</td>
                            <td></td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-base font-bold text-primary-700">{{ $lot->montant_affiche }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Rapport de fin de lot --}}
            @if ($lot->rapport)
                @php $rapport = $lot->rapport; @endphp
                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">Rapport de fin de lot</h3>
                        <a href="{{ route('rapports.show', $rapport) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">Voir le rapport {{ $rapport->code }}</a>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                        <div class="rounded-lg bg-green-50 p-3"><p class="text-green-700">Vendus</p><p class="text-lg font-bold text-green-800">{{ $rapport->quantite_vendue }}</p></div>
                        <div class="rounded-lg bg-red-50 p-3"><p class="text-red-700">Défectueux</p><p class="text-lg font-bold text-red-800">{{ $rapport->quantite_defectueuse }}</p></div>
                        <div class="rounded-lg bg-gray-50 p-3"><p class="text-gray-500">Rendus</p><p class="text-lg font-bold">{{ $rapport->quantite_rendue }}</p></div>
                        <div class="rounded-lg bg-primary-50 p-3"><p class="text-primary-700">Vendu</p><p class="text-lg font-bold text-primary-800">{{ Format::gnf($rapport->montant_vendu) }}</p></div>
                    </div>
                    <div class="mt-3"><x-badge :color="$rapport->ecart_color">{{ $rapport->ecart_label }}</x-badge></div>
                </div>
            @elseif ($lot->statut === 'en_cours')
                <div class="card flex flex-col items-start gap-4 border-primary-200 bg-primary-50/50 p-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">Rapport de fin de lot</h3>
                        <p class="mt-1 text-sm text-gray-600">L'agent a fini ? Saisissez les tickets vendus, défectueux et rendus, et l'argent versé.</p>
                    </div>
                    <a href="{{ route('rapports.create', $lot) }}" class="shrink-0"><x-primary-button type="button">Saisir le rapport</x-primary-button></a>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            {{-- Prévisionnel --}}
            <div class="card bg-primary-600 p-6 text-white">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-primary-100">Si tout est vendu</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-primary-100">Montant total</dt><dd class="font-semibold">{{ $lot->montant_affiche }}</dd></div>
                    <div class="flex justify-between"><dt class="text-primary-100">Agent ({{ Format::pourcent($lot->taux_agent) }})</dt><dd>− {{ Format::gnf($lot->commission_agent_prevue) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-primary-100">Superviseur ({{ Format::pourcent($lot->taux_superviseur) }})</dt><dd>− {{ Format::gnf($lot->commission_superviseur_prevue) }}</dd></div>
                    <div class="flex justify-between border-t border-white/20 pt-2 text-base"><dt class="font-semibold">Net gérant</dt><dd class="font-bold">{{ Format::gnf($lot->net_gerant_prevu) }}</dd></div>
                </dl>
            </div>

            {{-- Actions --}}
            @if ($lot->statut !== 'termine')
                <div class="card p-6">
                    <h3 class="font-semibold text-gray-900">Actions</h3>
                    <form method="POST" action="{{ route('lots.annulation', $lot) }}" class="mt-4">
                        @csrf
                        @method('PATCH')
                        @if ($lot->statut === 'annule')
                            <p class="text-sm text-gray-500">Ce lot est annulé. Vous pouvez le remettre en cours.</p>
                            <x-secondary-button type="submit" class="mt-3 w-full">Remettre en cours</x-secondary-button>
                        @else
                            <p class="text-sm text-gray-500">Annuler le lot s'il n'a finalement pas été remis (il reste dans l'historique).</p>
                            <x-secondary-button type="submit" class="mt-3 w-full">Annuler le lot</x-secondary-button>
                        @endif
                    </form>
                </div>
            @endif

            <div class="card space-y-1 p-6 text-sm text-gray-500">
                <p>Créé le {{ $lot->created_at->format('d/m/Y à H:i') }}</p>
                <p>Modifié le {{ $lot->updated_at->format('d/m/Y à H:i') }}</p>
            </div>

            @if ($lot->statut !== 'termine')
                <div class="card border-red-200 p-6">
                    <h3 class="font-semibold text-red-700">Zone de danger</h3>
                    <p class="mt-1 text-sm text-gray-500">Supprimer ce lot le retire des listes. S'il n'a pas été remis, préférez « Annuler le lot ».</p>
                    <x-confirm-delete :action="route('lots.destroy', $lot)" name="supprimer-lot"
                                      :message="'Le lot '.$lot->code.' sera supprimé.'">
                        <x-danger-button type="button" class="mt-4">Supprimer le lot</x-danger-button>
                    </x-confirm-delete>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
