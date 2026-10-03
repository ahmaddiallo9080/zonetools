<x-app-layout>
    <x-slot name="title">{{ $superviseur->nom_complet }}</x-slot>
    <x-slot name="header">{{ $superviseur->nom_complet }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('superviseurs.edit', $superviseur) }}"><x-primary-button type="button">Modifier</x-primary-button></a>
    </x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('superviseurs.index') }}" class="hover:text-primary-600">Superviseurs</a> / <span class="text-gray-700">{{ $superviseur->nom_complet }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Fiche --}}
            <div class="card p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <x-avatar :initiales="$superviseur->initiales" size="lg" />
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $superviseur->nom_complet }}</h2>
                            <p class="font-mono text-xs text-gray-500">{{ $superviseur->code }} · Superviseur</p>
                        </div>
                    </div>
                    <x-badge :color="$superviseur->statut_color">{{ $superviseur->statut_label }}</x-badge>
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-gray-500">Téléphone</dt><dd class="mt-0.5 font-medium">{{ $superviseur->telephone }}</dd></div>
                    <div><dt class="text-gray-500">Second téléphone</dt><dd class="mt-0.5 font-medium">{{ $superviseur->telephone2 ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">E-mail</dt><dd class="mt-0.5 font-medium">{{ $superviseur->email ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Pièce d'identité</dt><dd class="mt-0.5 font-medium">{{ $superviseur->piece_identite ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-gray-500">Adresse</dt><dd class="mt-0.5 font-medium">{{ $superviseur->adresse ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Date d'embauche</dt><dd class="mt-0.5 font-medium">{{ $superviseur->date_embauche?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Commission par défaut</dt><dd class="mt-0.5 font-medium">{{ $superviseur->taux_affiche }}</dd></div>
                </dl>

                @if ($superviseur->notes)
                    <div class="mt-6 whitespace-pre-line rounded-lg bg-gray-50 p-4 text-sm text-gray-700">{{ $superviseur->notes }}</div>
                @endif
            </div>

            {{-- Sites --}}
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4">
                    <h3 class="font-semibold text-gray-900">Sites supervisés <span class="text-gray-400">({{ $superviseur->sites->count() }})</span></h3>
                    <a href="{{ route('superviseurs.edit', $superviseur) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">Gérer les sites</a>
                </div>
                @if ($superviseur->sites->isEmpty())
                    <p class="border-t border-gray-100 px-6 py-6 text-sm text-gray-500">Aucun site rattaché.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-100 border-t border-gray-100 text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($superviseur->sites as $site)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3"><a href="{{ route('sites.show', $site) }}" class="font-medium text-gray-900 hover:text-primary-600">{{ $site->nom }}</a>
                                        <span class="block text-xs text-gray-500">{{ $site->localisation ?? '—' }}</span></td>
                                    <td class="px-6 py-3 text-gray-600">{{ $site->agents_count }} agent(s)</td>
                                    <td class="px-6 py-3 text-right"><x-badge :color="$site->statut_color">{{ $site->statut_label }}</x-badge></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Agents --}}
            <div class="card overflow-hidden">
                <div class="px-6 py-4">
                    <h3 class="font-semibold text-gray-900">Agents sous sa supervision <span class="text-gray-400">({{ $agents->count() }})</span></h3>
                </div>
                @if ($agents->isEmpty())
                    <p class="border-t border-gray-100 px-6 py-6 text-sm text-gray-500">Aucun agent sur ses sites.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-100 border-t border-gray-100 text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($agents as $agent)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3">
                                        <a href="{{ route('agents.show', $agent) }}" class="flex items-center gap-3">
                                            <x-avatar :initiales="$agent->initiales" size="sm" />
                                            <span class="font-medium text-gray-900 hover:text-primary-600">{{ $agent->nom_complet }}</span>
                                        </a>
                                    </td>
                                    <td class="px-6 py-3 text-gray-600">{{ $agent->site?->nom }}</td>
                                    <td class="px-6 py-3 text-gray-600">{{ $agent->telephone }}</td>
                                    <td class="px-6 py-3 text-right"><x-badge :color="$agent->statut_color">{{ $agent->statut_label }}</x-badge></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="space-y-6">
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
                        <a href="{{ route('paiements.create', ['beneficiaire' => 'superviseur:'.$superviseur->id]) }}" class="text-green-700 hover:text-green-800">Payer</a>
                    @endif
                    <a href="{{ route('paiements.index', ['beneficiaire' => 'superviseur:'.$superviseur->id]) }}" class="text-primary-600 hover:text-primary-700">Historique</a>
                </div>
            </div>

            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">Lots en cours</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Lots</dt><dd class="font-semibold">{{ $statsLots->lots }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Tickets</dt><dd class="font-semibold">{{ number_format($statsLots->tickets, 0, ',', ' ') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Valeur</dt><dd class="font-semibold">{{ \App\Support\Format::gnf($statsLots->montant) }}</dd></div>
                </dl>
                <a href="{{ route('lots.index', ['superviseur' => $superviseur->id]) }}" class="mt-4 inline-block text-sm font-medium text-primary-600 hover:text-primary-700">Voir ses lots</a>
            </div>

            <div class="card space-y-1 p-6 text-sm text-gray-500">
                <p>Créé le {{ $superviseur->created_at->format('d/m/Y à H:i') }}</p>
                <p>Modifié le {{ $superviseur->updated_at->format('d/m/Y à H:i') }}</p>
            </div>

            <div class="card border-red-200 p-6">
                <h3 class="font-semibold text-red-700">Zone de danger</h3>
                <p class="mt-1 text-sm text-gray-500">Ses sites resteront enregistrés mais sans superviseur.</p>
                <x-confirm-delete :action="route('superviseurs.destroy', $superviseur)" name="supprimer-superviseur"
                                  :message="'Le superviseur « '.$superviseur->nom_complet.' » sera supprimé.'">
                    <x-danger-button type="button" class="mt-4">Supprimer le superviseur</x-danger-button>
                </x-confirm-delete>
            </div>
        </div>
    </div>
</x-app-layout>
