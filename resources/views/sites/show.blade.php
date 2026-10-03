<x-app-layout>
    <x-slot name="title">{{ $site->nom }}</x-slot>
    <x-slot name="header">{{ $site->nom }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('sites.edit', $site) }}"><x-primary-button type="button">Modifier</x-primary-button></a>
    </x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('sites.index') }}" class="hover:text-primary-600">Sites</a> / <span class="text-gray-700">{{ $site->nom }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Fiche --}}
            <div class="card p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-600 text-white">
                            <x-icon name="wifi" class="h-6 w-6" />
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $site->nom }}</h2>
                            <p class="font-mono text-xs text-gray-500">{{ $site->code }}</p>
                        </div>
                    </div>
                    <x-badge :color="$site->statut_color">{{ $site->statut_label }}</x-badge>
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-gray-500">Ville</dt><dd class="mt-0.5 font-medium">{{ $site->ville ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Quartier</dt><dd class="mt-0.5 font-medium">{{ $site->quartier ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-gray-500">Adresse / repère</dt><dd class="mt-0.5 font-medium">{{ $site->adresse ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Téléphone</dt><dd class="mt-0.5 font-medium">{{ $site->telephone ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Date d'ouverture</dt><dd class="mt-0.5 font-medium">{{ $site->date_ouverture?->format('d/m/Y') ?? '—' }}</dd></div>
                </dl>

                @if ($site->notes)
                    <div class="mt-6 rounded-lg bg-gray-50 p-4 text-sm text-gray-700 whitespace-pre-line">{{ $site->notes }}</div>
                @endif
            </div>

            {{-- Tarifs du site --}}
            <div class="card overflow-hidden">
                <div class="px-6 py-4">
                    <h3 class="font-semibold text-gray-900">Tarifs des forfaits</h3>
                    <p class="text-sm text-gray-500">Prix de vente des tickets sur ce site.</p>
                </div>
                @if ($tarifs->isEmpty())
                    <p class="border-t border-gray-100 px-6 py-6 text-sm text-gray-500">Aucun forfait actif. <a href="{{ route('forfaits.create') }}" class="font-medium text-primary-600">Créer un forfait</a></p>
                @else
                    <table class="min-w-full divide-y divide-gray-100 border-t border-gray-100 text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($tarifs as $tarif)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3"><a href="{{ route('forfaits.show', $tarif['forfait']) }}"><x-badge :color="$tarif['forfait']->couleur">{{ $tarif['forfait']->nom }}</x-badge></a></td>
                                    <td class="px-6 py-3 text-gray-600">{{ $tarif['forfait']->duree_label }} · {{ $tarif['forfait']->appareils_label }}</td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right">
                                        <span class="font-semibold {{ $tarif['particulier'] ? 'text-amber-700' : 'text-gray-900' }}">{{ \App\Support\Format::gnf($tarif['prix']) }}</span>
                                        @if ($tarif['particulier'])
                                            <span class="block text-xs text-gray-400 line-through">{{ $tarif['forfait']->prix_affiche }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            @include('lots._liste-courte', [
                'titre' => 'Derniers lots de tickets',
                'lienTous' => route('lots.index', ['site' => $site->id]),
                'lienNouveau' => route('lots.create', ['site' => $site->id]),
            ])
        </div>

        <div class="space-y-6">
            {{-- Équipe du site --}}
            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">Superviseur</h3>
                @if ($site->superviseur)
                    <a href="{{ route('superviseurs.show', $site->superviseur) }}" class="mt-3 flex items-center gap-3 rounded-lg p-2 -mx-2 hover:bg-gray-50">
                        <x-avatar :initiales="$site->superviseur->initiales" />
                        <span class="text-sm">
                            <span class="block font-medium text-gray-900">{{ $site->superviseur->nom_complet }}</span>
                            <span class="block text-gray-500">{{ $site->superviseur->telephone }}</span>
                        </span>
                    </a>
                @else
                    <p class="mt-2 text-sm text-amber-700">Aucun superviseur affecté.</p>
                    <a href="{{ route('sites.edit', $site) }}" class="mt-1 inline-block text-sm font-medium text-primary-600">Affecter un superviseur</a>
                @endif
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Agents <span class="text-gray-400">({{ $site->agents->count() }})</span></h3>
                    <a href="{{ route('agents.create', ['site' => $site->id]) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">+ Ajouter</a>
                </div>
                @forelse ($site->agents as $agent)
                    <a href="{{ route('agents.show', $agent) }}" class="mt-3 flex items-center gap-3 rounded-lg p-2 -mx-2 hover:bg-gray-50">
                        <x-avatar :initiales="$agent->initiales" size="sm" />
                        <span class="min-w-0 flex-1 text-sm">
                            <span class="block truncate font-medium text-gray-900">{{ $agent->nom_complet }}</span>
                            <span class="block text-gray-500">{{ $agent->telephone }}</span>
                        </span>
                        <x-badge :color="$agent->statut_color">{{ $agent->statut_label }}</x-badge>
                    </a>
                @empty
                    <p class="mt-2 text-sm text-gray-500">Aucun agent sur ce site.</p>
                @endforelse
            </div>

            {{-- Dépenses du site --}}
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Dépenses</h3>
                    <a href="{{ route('depenses.create', ['site' => $site->id]) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">+ Ajouter</a>
                </div>
                <p class="mt-1 text-sm text-gray-500">Ce mois-ci : <span class="font-semibold text-gray-900">{{ \App\Support\Format::gnf($depensesMois) }}</span></p>
                @forelse ($depenses as $depense)
                    <a href="{{ route('depenses.show', $depense) }}" class="-mx-2 mt-2 flex items-center justify-between gap-3 rounded-lg p-2 text-sm hover:bg-gray-50">
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-gray-900">{{ $depense->libelle }}</span>
                            <span class="block text-xs text-gray-500">{{ $depense->date_depense->format('d/m/Y') }} · {{ $depense->categorie_label }}</span>
                        </span>
                        <span class="shrink-0 font-semibold tabular-nums">{{ $depense->montant_affiche }}</span>
                    </a>
                @empty
                    <p class="mt-2 text-sm text-gray-500">Aucune dépense enregistrée.</p>
                @endforelse
                @if ($depenses->isNotEmpty())
                    <a href="{{ route('depenses.index', ['site' => $site->id]) }}" class="mt-3 inline-block text-sm font-medium text-gray-500 hover:text-primary-600">Toutes les dépenses du site</a>
                @endif
            </div>

            <div class="card p-6 text-sm text-gray-500 space-y-1">
                <p>Créé le {{ $site->created_at->format('d/m/Y à H:i') }}</p>
                <p>Modifié le {{ $site->updated_at->format('d/m/Y à H:i') }}</p>
            </div>

            <div class="card border-red-200 p-6">
                <h3 class="font-semibold text-red-700">Zone de danger</h3>
                <p class="mt-1 text-sm text-gray-500">Supprimer ce site le retire de toutes les listes. Ses agents se retrouveront sans site.</p>
                <x-confirm-delete :action="route('sites.destroy', $site)" name="supprimer-site"
                                  :message="'Le site « '.$site->nom.' » sera supprimé de la liste.'">
                    <x-danger-button type="button" class="mt-4">Supprimer le site</x-danger-button>
                </x-confirm-delete>
            </div>
        </div>
    </div>
</x-app-layout>
