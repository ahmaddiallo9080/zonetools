<x-app-layout>
    <x-slot name="title">Agents</x-slot>
    <x-slot name="header">Agents</x-slot>
    <x-slot name="actions">
        <a href="{{ route('agents.create') }}">
            <x-primary-button type="button"><span class="text-lg leading-none">+</span> Nouvel agent</x-primary-button>
        </a>
    </x-slot>

    @php $filtres = ['q', 'statut', 'site', 'superviseur']; @endphp

    {{-- Compteurs --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <a href="{{ route('agents.index') }}" class="card p-4 hover:border-primary-300 {{ request()->hasAny($filtres) ? '' : 'ring-2 ring-primary-500' }}">
            <p class="text-sm text-gray-500">Total</p>
            <p class="mt-1 text-2xl font-bold">{{ $compteurs->sum() }}</p>
        </a>
        @foreach (\App\Models\Agent::statuts() as $valeur => $statut)
            <a href="{{ route('agents.index', ['statut' => $valeur]) }}" class="card p-4 hover:border-primary-300 {{ request('statut') === $valeur ? 'ring-2 ring-primary-500' : '' }}">
                <x-badge :color="$statut['color']">{{ $statut['label'] }}</x-badge>
                <p class="mt-2 text-2xl font-bold">{{ $compteurs[$valeur] ?? 0 }}</p>
            </a>
        @endforeach
        <a href="{{ route('agents.index', ['site' => 'aucun']) }}" class="card p-4 hover:border-primary-300 {{ request('site') === 'aucun' ? 'ring-2 ring-primary-500' : '' }}">
            <x-badge color="amber">Sans site</x-badge>
            <p class="mt-2 text-2xl font-bold">{{ $sansSite }}</p>
        </a>
    </div>

    {{-- Recherche & filtres --}}
    <form method="GET" action="{{ route('agents.index') }}" class="card mt-6 grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_auto_auto_auto_auto] lg:items-center">
        <input type="hidden" name="tri" value="{{ $tri }}">
        <input type="hidden" name="sens" value="{{ $sens }}">
        <x-text-input name="q" type="search" :value="request('q')" placeholder="Rechercher par nom, prénom, code, téléphone..." class="w-full sm:col-span-2 lg:col-span-1" />
        <x-select name="site" onchange="this.form.submit()">
            <option value="">Tous les sites</option>
            <option value="aucun" @selected(request('site') === 'aucun')>Sans site</option>
            @foreach ($sites as $site)
                <option value="{{ $site->id }}" @selected(request('site') == $site->id)>{{ $site->nom }}</option>
            @endforeach
        </x-select>
        <x-select name="superviseur" onchange="this.form.submit()">
            <option value="">Tous les superviseurs</option>
            @foreach ($superviseurs as $sup)
                <option value="{{ $sup->id }}" @selected(request('superviseur') == $sup->id)>{{ $sup->nom_complet }}</option>
            @endforeach
        </x-select>
        <x-select name="statut" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            @foreach (\App\Models\Agent::statuts() as $valeur => $statut)
                <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $statut['label'] }}</option>
            @endforeach
        </x-select>
        <div class="flex gap-2">
            <x-primary-button>Filtrer</x-primary-button>
            @if (request()->hasAny($filtres))
                <a href="{{ route('agents.index') }}"><x-secondary-button>Réinitialiser</x-secondary-button></a>
            @endif
        </div>
    </form>

    {{-- Liste --}}
    <div class="card mt-6 overflow-hidden">
        @if ($agents->isEmpty())
            <div class="flex flex-col items-center px-6 py-16 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600"><x-icon name="users" class="h-6 w-6" /></span>
                @if (request()->hasAny($filtres))
                    <h3 class="mt-4 font-semibold text-gray-900">Aucun agent ne correspond à votre recherche</h3>
                    <a href="{{ route('agents.index') }}" class="mt-2 text-sm font-medium text-primary-600 hover:text-primary-700">Voir tous les agents</a>
                @else
                    <h3 class="mt-4 font-semibold text-gray-900">Aucun agent pour le moment</h3>
                    <p class="mt-1 text-sm text-gray-500">Ajoutez les agents qui vendent les tickets sur vos sites.</p>
                    <a href="{{ route('agents.create') }}" class="mt-4"><x-primary-button type="button">+ Nouvel agent</x-primary-button></a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3"><x-sort-link column="code" :tri="$tri" :sens="$sens">Code</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="nom" :tri="$tri" :sens="$sens">Agent</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="telephone" :tri="$tri" :sens="$sens">Téléphone</x-sort-link></th>
                            <th class="px-4 py-3">Site</th>
                            <th class="px-4 py-3">Superviseur</th>
                            <th class="px-4 py-3 text-right"><x-sort-link column="taux_commission" :tri="$tri" :sens="$sens">Commission</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="statut" :tri="$tri" :sens="$sens">Statut</x-sort-link></th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($agents as $agent)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-500">{{ $agent->code }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('agents.show', $agent) }}" class="flex items-center gap-3">
                                        <x-avatar :initiales="$agent->initiales" size="sm" />
                                        <span class="font-medium text-gray-900 hover:text-primary-600">{{ $agent->nom_complet }}</span>
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $agent->telephone }}</td>
                                <td class="px-4 py-3">
                                    @if ($agent->site)
                                        <a href="{{ route('sites.show', $agent->site) }}" class="text-gray-700 hover:text-primary-600">{{ $agent->site->nom }}</a>
                                    @else
                                        <x-badge color="amber">Sans site</x-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if ($agent->superviseur)
                                        <a href="{{ route('superviseurs.show', $agent->superviseur) }}" class="hover:text-primary-600">{{ $agent->superviseur->nom_complet }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-600">{{ $agent->taux_affiche }}</td>
                                <td class="px-4 py-3"><x-badge :color="$agent->statut_color">{{ $agent->statut_label }}</x-badge></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <x-table-actions
                                        :show="route('agents.show', $agent)"
                                        :edit="route('agents.edit', $agent)"
                                        :destroy="route('agents.destroy', $agent)"
                                        :name="'supprimer-agent-'.$agent->id"
                                        :message="'L\'agent « '.$agent->nom_complet.' » sera supprimé de la liste.'" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($agents->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">{{ $agents->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
