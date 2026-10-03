<x-app-layout>
    <x-slot name="title">{{ $agent->nom_complet }}</x-slot>
    <x-slot name="header">{{ $agent->nom_complet }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('agents.edit', $agent) }}"><x-primary-button type="button">Modifier</x-primary-button></a>
    </x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('agents.index') }}" class="hover:text-primary-600">Agents</a> / <span class="text-gray-700">{{ $agent->nom_complet }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <x-avatar :initiales="$agent->initiales" size="lg" />
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $agent->nom_complet }}</h2>
                            <p class="font-mono text-xs text-gray-500">{{ $agent->code }} · Agent</p>
                        </div>
                    </div>
                    <x-badge :color="$agent->statut_color">{{ $agent->statut_label }}</x-badge>
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-gray-500">Téléphone</dt><dd class="mt-0.5 font-medium">{{ $agent->telephone }}</dd></div>
                    <div><dt class="text-gray-500">Second téléphone</dt><dd class="mt-0.5 font-medium">{{ $agent->telephone2 ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">E-mail</dt><dd class="mt-0.5 font-medium">{{ $agent->email ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Pièce d'identité</dt><dd class="mt-0.5 font-medium">{{ $agent->piece_identite ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-gray-500">Adresse</dt><dd class="mt-0.5 font-medium">{{ $agent->adresse ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Date d'embauche</dt><dd class="mt-0.5 font-medium">{{ $agent->date_embauche?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Commission par défaut</dt><dd class="mt-0.5 font-medium">{{ $agent->taux_affiche }}</dd></div>
                </dl>

                @if ($agent->notes)
                    <div class="mt-6 whitespace-pre-line rounded-lg bg-gray-50 p-4 text-sm text-gray-700">{{ $agent->notes }}</div>
                @endif
            </div>

            @include('lots._liste-courte', [
                'titre' => 'Derniers lots remis',
                'lienTous' => route('lots.index', ['agent' => $agent->id]),
                'lienNouveau' => $agent->site_id ? route('lots.create', ['agent' => $agent->id]) : null,
            ])
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">Affectation</h3>
                @if ($agent->site)
                    <div class="mt-4 space-y-4 text-sm">
                        <div>
                            <p class="text-gray-500">Site</p>
                            <a href="{{ route('sites.show', $agent->site) }}" class="mt-0.5 flex items-center gap-2 font-medium text-gray-900 hover:text-primary-600">
                                <x-icon name="wifi" class="h-4 w-4 text-primary-600" /> {{ $agent->site->nom }}
                            </a>
                        </div>
                        <div>
                            <p class="text-gray-500">Superviseur</p>
                            @if ($agent->superviseur)
                                <a href="{{ route('superviseurs.show', $agent->superviseur) }}" class="mt-0.5 flex items-center gap-2 font-medium text-gray-900 hover:text-primary-600">
                                    <x-avatar :initiales="$agent->superviseur->initiales" size="sm" /> {{ $agent->superviseur->nom_complet }}
                                </a>
                            @else
                                <p class="mt-0.5 text-gray-400">Le site n'a pas de superviseur</p>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="mt-2 text-sm text-amber-700">Cet agent n'est rattaché à aucun site.</p>
                    <a href="{{ route('agents.edit', $agent) }}" class="mt-2 inline-block text-sm font-medium text-primary-600">Affecter à un site</a>
                @endif
            </div>

            {{-- Commissions --}}
            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">Commissions</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Gagnées</dt><dd class="font-medium">{{ \App\Support\Format::gnf($commission->gagnees) }}</dd></div>
                    @if ($commission->gardees)
                        <div class="flex justify-between"><dt class="text-gray-500">Gardées sur les ventes</dt><dd class="font-medium">{{ \App\Support\Format::gnf($commission->gardees) }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-gray-500">Déjà payé</dt><dd class="font-medium text-green-700">{{ \App\Support\Format::gnf($commission->payees) }}</dd></div>
                    <div class="flex justify-between border-t border-gray-100 pt-2"><dt class="font-medium">Reste à payer</dt><dd class="font-bold text-primary-700">{{ \App\Support\Format::gnf($commission->solde) }}</dd></div>
                    @if ($commission->manquants)
                        <div class="flex justify-between text-red-600"><dt>Manquants (argent non versé)</dt><dd class="font-medium">{{ \App\Support\Format::gnf($commission->manquants) }}</dd></div>
                    @endif
                </dl>
                <div class="mt-4 flex gap-4 text-sm font-medium">
                    @if ($commission->solde > 0)
                        <a href="{{ route('paiements.create', ['beneficiaire' => 'agent:'.$agent->id]) }}" class="text-green-700 hover:text-green-800">Payer</a>
                    @endif
                    <a href="{{ route('paiements.index', ['beneficiaire' => 'agent:'.$agent->id]) }}" class="text-primary-600 hover:text-primary-700">Historique</a>
                </div>
            </div>

            <div class="card space-y-1 p-6 text-sm text-gray-500">
                <p>Créé le {{ $agent->created_at->format('d/m/Y à H:i') }}</p>
                <p>Modifié le {{ $agent->updated_at->format('d/m/Y à H:i') }}</p>
            </div>

            <div class="card border-red-200 p-6">
                <h3 class="font-semibold text-red-700">Zone de danger</h3>
                <p class="mt-1 text-sm text-gray-500">L'agent sera retiré de toutes les listes.</p>
                <x-confirm-delete :action="route('agents.destroy', $agent)" name="supprimer-agent"
                                  :message="'L\'agent « '.$agent->nom_complet.' » sera supprimé.'">
                    <x-danger-button type="button" class="mt-4">Supprimer l'agent</x-danger-button>
                </x-confirm-delete>
            </div>
        </div>
    </div>
</x-app-layout>
