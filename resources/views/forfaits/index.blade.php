<x-app-layout>
    <x-slot name="title">Forfaits</x-slot>
    <x-slot name="header">Forfaits de tickets</x-slot>
    <x-slot name="actions">
        <a href="{{ route('forfaits.create') }}">
            <x-primary-button type="button"><span class="text-lg leading-none">+</span> Nouveau forfait</x-primary-button>
        </a>
    </x-slot>

    {{-- Compteurs --}}
    <div class="grid grid-cols-3 gap-4">
        <a href="{{ route('forfaits.index') }}" class="card p-4 hover:border-primary-300 {{ request('statut') ? '' : 'ring-2 ring-primary-500' }}">
            <p class="text-sm text-gray-500">Total</p>
            <p class="mt-1 text-2xl font-bold">{{ $compteurs->sum() }}</p>
        </a>
        @foreach (\App\Models\Forfait::STATUTS as $valeur => $statut)
            <a href="{{ route('forfaits.index', ['statut' => $valeur]) }}" class="card p-4 hover:border-primary-300 {{ request('statut') === $valeur ? 'ring-2 ring-primary-500' : '' }}">
                <x-badge :color="$statut['color']">{{ $statut['label'] }}</x-badge>
                <p class="mt-2 text-2xl font-bold">{{ $compteurs[$valeur] ?? 0 }}</p>
            </a>
        @endforeach
    </div>

    {{-- Recherche --}}
    <form method="GET" action="{{ route('forfaits.index') }}" class="card mt-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="hidden" name="tri" value="{{ $tri }}">
        <input type="hidden" name="sens" value="{{ $sens }}">
        <x-text-input name="q" type="search" :value="request('q')" placeholder="Rechercher par nom, code, description..." class="w-full sm:flex-1" />
        <x-select name="statut" class="sm:w-44" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            @foreach (\App\Models\Forfait::STATUTS as $valeur => $statut)
                <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $statut['label'] }}</option>
            @endforeach
        </x-select>
        <div class="flex gap-2">
            <x-primary-button>Filtrer</x-primary-button>
            @if (request()->hasAny(['q', 'statut']))
                <a href="{{ route('forfaits.index') }}"><x-secondary-button>Réinitialiser</x-secondary-button></a>
            @endif
        </div>
    </form>

    {{-- Liste --}}
    <div class="card mt-6 overflow-hidden">
        @if ($forfaits->isEmpty())
            <div class="flex flex-col items-center px-6 py-16 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600"><x-icon name="tag" class="h-6 w-6" /></span>
                @if (request()->hasAny(['q', 'statut']))
                    <h3 class="mt-4 font-semibold text-gray-900">Aucun forfait ne correspond à votre recherche</h3>
                    <a href="{{ route('forfaits.index') }}" class="mt-2 text-sm font-medium text-primary-600 hover:text-primary-700">Voir tous les forfaits</a>
                @else
                    <h3 class="mt-4 font-semibold text-gray-900">Aucun forfait pour le moment</h3>
                    <p class="mt-1 text-sm text-gray-500">Créez vos forfaits (1 heure, 1 jour, 1 semaine...) avant de distribuer des lots.</p>
                    <a href="{{ route('forfaits.create') }}" class="mt-4"><x-primary-button type="button">+ Nouveau forfait</x-primary-button></a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3"><x-sort-link column="code" :tri="$tri" :sens="$sens">Code</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="nom" :tri="$tri" :sens="$sens">Forfait</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="duree_minutes" :tri="$tri" :sens="$sens">Durée</x-sort-link></th>
                            <th class="px-4 py-3 text-center"><x-sort-link column="nb_appareils" :tri="$tri" :sens="$sens">Appareils</x-sort-link></th>
                            <th class="px-4 py-3 text-right"><x-sort-link column="prix" :tri="$tri" :sens="$sens">Prix de base</x-sort-link></th>
                            <th class="px-4 py-3 text-center"><x-sort-link column="sites_count" :tri="$tri" :sens="$sens">Prix particuliers</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="statut" :tri="$tri" :sens="$sens">Statut</x-sort-link></th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($forfaits as $forfait)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-500">{{ $forfait->code }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('forfaits.show', $forfait) }}" class="flex items-center gap-2">
                                        <x-badge :color="$forfait->couleur">{{ $forfait->nom }}</x-badge>
                                    </a>
                                    @if ($forfait->description)
                                        <p class="mt-1 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($forfait->description, 50) }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $forfait->duree_label }}</td>
                                <td class="px-4 py-3 text-center text-gray-700">{{ $forfait->nb_appareils }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-900">{{ $forfait->prix_affiche }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($forfait->sites_count)
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">{{ $forfait->sites_count }} site(s)</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3"><x-badge :color="$forfait->statut_color">{{ $forfait->statut_label }}</x-badge></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <x-table-actions
                                        :show="route('forfaits.show', $forfait)"
                                        :edit="route('forfaits.edit', $forfait)"
                                        :destroy="route('forfaits.destroy', $forfait)"
                                        :name="'supprimer-forfait-'.$forfait->id"
                                        :message="'Le forfait « '.$forfait->nom.' » sera supprimé de la liste.'" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($forfaits->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">{{ $forfaits->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
