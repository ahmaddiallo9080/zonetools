<x-app-layout>
    <x-slot name="title">Sites / Hotspots</x-slot>
    <x-slot name="header">Sites / Hotspots</x-slot>
    <x-slot name="actions">
        <a href="{{ route('sites.create') }}">
            <x-primary-button type="button"><span class="text-lg leading-none">+</span> Nouveau site</x-primary-button>
        </a>
    </x-slot>

    {{-- Compteurs par statut --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <a href="{{ route('sites.index') }}" class="card p-4 hover:border-primary-300 {{ request('statut') ? '' : 'ring-2 ring-primary-500' }}">
            <p class="text-sm text-gray-500">Total</p>
            <p class="mt-1 text-2xl font-bold">{{ $compteurs->sum() }}</p>
        </a>
        @foreach (\App\Models\Site::STATUTS as $valeur => $statut)
            <a href="{{ route('sites.index', ['statut' => $valeur]) }}" class="card p-4 hover:border-primary-300 {{ request('statut') === $valeur ? 'ring-2 ring-primary-500' : '' }}">
                <x-badge :color="$statut['color']">{{ $statut['label'] }}</x-badge>
                <p class="mt-2 text-2xl font-bold">{{ $compteurs[$valeur] ?? 0 }}</p>
            </a>
        @endforeach
    </div>

    {{-- Recherche --}}
    <form method="GET" action="{{ route('sites.index') }}" class="card mt-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="hidden" name="tri" value="{{ $tri }}">
        <input type="hidden" name="sens" value="{{ $sens }}">
        <x-text-input name="q" type="search" :value="request('q')" placeholder="Rechercher par nom, code, ville, quartier, téléphone..." class="w-full sm:flex-1" />
        <x-select name="superviseur" class="sm:w-52" onchange="this.form.submit()">
            <option value="">Tous les superviseurs</option>
            <option value="aucun" @selected(request('superviseur') === 'aucun')>Sans superviseur</option>
            @foreach ($superviseurs as $sup)
                <option value="{{ $sup->id }}" @selected(request('superviseur') == $sup->id)>{{ $sup->nom_complet }}</option>
            @endforeach
        </x-select>
        <x-select name="statut" class="sm:w-48" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            @foreach (\App\Models\Site::STATUTS as $valeur => $statut)
                <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $statut['label'] }}</option>
            @endforeach
        </x-select>
        <div class="flex gap-2">
            <x-primary-button>Filtrer</x-primary-button>
            @if (request()->hasAny(['q', 'statut', 'superviseur']))
                <a href="{{ route('sites.index') }}"><x-secondary-button>Réinitialiser</x-secondary-button></a>
            @endif
        </div>
    </form>

    {{-- Liste --}}
    <div class="card mt-6 overflow-hidden">
        @if ($sites->isEmpty())
            <div class="flex flex-col items-center px-6 py-16 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600">
                    <x-icon name="wifi" class="h-6 w-6" />
                </span>
                @if (request()->hasAny(['q', 'statut', 'superviseur']))
                    <h3 class="mt-4 font-semibold text-gray-900">Aucun site ne correspond à votre recherche</h3>
                    <a href="{{ route('sites.index') }}" class="mt-2 text-sm font-medium text-primary-600 hover:text-primary-700">Voir tous les sites</a>
                @else
                    <h3 class="mt-4 font-semibold text-gray-900">Aucun site pour le moment</h3>
                    <p class="mt-1 text-sm text-gray-500">Commencez par enregistrer votre premier hotspot.</p>
                    <a href="{{ route('sites.create') }}" class="mt-4"><x-primary-button type="button">+ Nouveau site</x-primary-button></a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3"><x-sort-link column="code" :tri="$tri" :sens="$sens">Code</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="nom" :tri="$tri" :sens="$sens">Site</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="ville" :tri="$tri" :sens="$sens">Localisation</x-sort-link></th>
                            <th class="px-4 py-3">Superviseur</th>
                            <th class="px-4 py-3 text-center"><x-sort-link column="agents_count" :tri="$tri" :sens="$sens">Agents</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="date_ouverture" :tri="$tri" :sens="$sens">Ouverture</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="statut" :tri="$tri" :sens="$sens">Statut</x-sort-link></th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($sites as $site)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-500">{{ $site->code }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('sites.show', $site) }}" class="font-medium text-gray-900 hover:text-primary-600">{{ $site->nom }}</a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $site->localisation ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if ($site->superviseur)
                                        <a href="{{ route('superviseurs.show', $site->superviseur) }}" class="hover:text-primary-600">{{ $site->superviseur->nom_complet }}</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center font-medium">{{ $site->agents_count }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $site->date_ouverture?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3"><x-badge :color="$site->statut_color">{{ $site->statut_label }}</x-badge></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <x-table-actions
                                        :show="route('sites.show', $site)"
                                        :edit="route('sites.edit', $site)"
                                        :destroy="route('sites.destroy', $site)"
                                        :name="'supprimer-site-'.$site->id"
                                        :message="'Le site « '.$site->nom.' » sera supprimé de la liste.'" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($sites->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">{{ $sites->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
