<x-app-layout>
    <x-slot name="title">Superviseurs</x-slot>
    <x-slot name="header">Superviseurs</x-slot>
    <x-slot name="actions">
        <a href="{{ route('superviseurs.create') }}">
            <x-primary-button type="button"><span class="text-lg leading-none">+</span> Nouveau superviseur</x-primary-button>
        </a>
    </x-slot>

    {{-- Compteurs --}}
    <div class="grid grid-cols-3 gap-4">
        <a href="{{ route('superviseurs.index') }}" class="card p-4 hover:border-primary-300 {{ request('statut') ? '' : 'ring-2 ring-primary-500' }}">
            <p class="text-sm text-gray-500">Total</p>
            <p class="mt-1 text-2xl font-bold">{{ $compteurs->sum() }}</p>
        </a>
        @foreach (\App\Models\Superviseur::statuts() as $valeur => $statut)
            <a href="{{ route('superviseurs.index', ['statut' => $valeur]) }}" class="card p-4 hover:border-primary-300 {{ request('statut') === $valeur ? 'ring-2 ring-primary-500' : '' }}">
                <x-badge :color="$statut['color']">{{ $statut['label'] }}</x-badge>
                <p class="mt-2 text-2xl font-bold">{{ $compteurs[$valeur] ?? 0 }}</p>
            </a>
        @endforeach
    </div>

    {{-- Recherche --}}
    <form method="GET" action="{{ route('superviseurs.index') }}" class="card mt-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="hidden" name="tri" value="{{ $tri }}">
        <input type="hidden" name="sens" value="{{ $sens }}">
        <x-text-input name="q" type="search" :value="request('q')" placeholder="Rechercher par nom, prénom, code, téléphone..." class="w-full sm:flex-1" />
        <x-select name="statut" class="sm:w-44" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            @foreach (\App\Models\Superviseur::statuts() as $valeur => $statut)
                <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $statut['label'] }}</option>
            @endforeach
        </x-select>
        <div class="flex gap-2">
            <x-primary-button>Filtrer</x-primary-button>
            @if (request()->hasAny(['q', 'statut']))
                <a href="{{ route('superviseurs.index') }}"><x-secondary-button>Réinitialiser</x-secondary-button></a>
            @endif
        </div>
    </form>

    {{-- Liste --}}
    <div class="card mt-6 overflow-hidden">
        @if ($superviseurs->isEmpty())
            <div class="flex flex-col items-center px-6 py-16 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600"><x-icon name="user-tie" class="h-6 w-6" /></span>
                @if (request()->hasAny(['q', 'statut']))
                    <h3 class="mt-4 font-semibold text-gray-900">Aucun superviseur ne correspond à votre recherche</h3>
                    <a href="{{ route('superviseurs.index') }}" class="mt-2 text-sm font-medium text-primary-600 hover:text-primary-700">Voir tous les superviseurs</a>
                @else
                    <h3 class="mt-4 font-semibold text-gray-900">Aucun superviseur pour le moment</h3>
                    <p class="mt-1 text-sm text-gray-500">Ajoutez vos superviseurs puis rattachez-leur des sites.</p>
                    <a href="{{ route('superviseurs.create') }}" class="mt-4"><x-primary-button type="button">+ Nouveau superviseur</x-primary-button></a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3"><x-sort-link column="code" :tri="$tri" :sens="$sens">Code</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="nom" :tri="$tri" :sens="$sens">Superviseur</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="telephone" :tri="$tri" :sens="$sens">Téléphone</x-sort-link></th>
                            <th class="px-4 py-3 text-center"><x-sort-link column="sites_count" :tri="$tri" :sens="$sens">Sites</x-sort-link></th>
                            <th class="px-4 py-3 text-center"><x-sort-link column="agents_count" :tri="$tri" :sens="$sens">Agents</x-sort-link></th>
                            <th class="px-4 py-3 text-right"><x-sort-link column="taux_commission" :tri="$tri" :sens="$sens">Commission</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="statut" :tri="$tri" :sens="$sens">Statut</x-sort-link></th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($superviseurs as $superviseur)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-500">{{ $superviseur->code }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('superviseurs.show', $superviseur) }}" class="flex items-center gap-3">
                                        <x-avatar :initiales="$superviseur->initiales" size="sm" />
                                        <span class="font-medium text-gray-900 hover:text-primary-600">{{ $superviseur->nom_complet }}</span>
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $superviseur->telephone }}</td>
                                <td class="px-4 py-3 text-center font-medium">{{ $superviseur->sites_count }}</td>
                                <td class="px-4 py-3 text-center font-medium">{{ $superviseur->agents_count }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-600">{{ $superviseur->taux_affiche }}</td>
                                <td class="px-4 py-3"><x-badge :color="$superviseur->statut_color">{{ $superviseur->statut_label }}</x-badge></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <x-table-actions
                                        :show="route('superviseurs.show', $superviseur)"
                                        :edit="route('superviseurs.edit', $superviseur)"
                                        :destroy="route('superviseurs.destroy', $superviseur)"
                                        :name="'supprimer-superviseur-'.$superviseur->id"
                                        :message="'Le superviseur « '.$superviseur->nom_complet.' » sera supprimé et ses sites n\'auront plus de superviseur.'" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($superviseurs->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">{{ $superviseurs->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
