<x-app-layout>
    <x-slot name="title">Lots de tickets</x-slot>
    <x-slot name="header">Lots de tickets</x-slot>
    <x-slot name="actions">
        <a href="{{ route('lots.create') }}">
            <x-primary-button type="button"><span class="text-lg leading-none">+</span> Nouveau lot</x-primary-button>
        </a>
    </x-slot>

    @php $filtres = ['q', 'statut', 'site', 'agent', 'superviseur', 'du', 'au']; @endphp

    {{-- Compteurs --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <a href="{{ route('lots.index') }}" class="card p-4 hover:border-primary-300 {{ request()->hasAny($filtres) ? '' : 'ring-2 ring-primary-500' }}">
            <p class="text-sm text-gray-500">Total</p>
            <p class="mt-1 text-2xl font-bold">{{ $compteurs->sum() }}</p>
        </a>
        @foreach (\App\Models\Lot::STATUTS as $valeur => $statut)
            <a href="{{ route('lots.index', ['statut' => $valeur]) }}" class="card p-4 hover:border-primary-300 {{ request('statut') === $valeur ? 'ring-2 ring-primary-500' : '' }}">
                <x-badge :color="$statut['color']">{{ $statut['label'] }}</x-badge>
                <p class="mt-2 text-2xl font-bold">{{ $compteurs[$valeur] ?? 0 }}</p>
            </a>
        @endforeach
        <div class="card col-span-2 bg-primary-600 p-4 text-white lg:col-span-1">
            <p class="text-sm text-primary-100">En circulation</p>
            <p class="mt-1 text-xl font-bold">{{ number_format($enCirculation->tickets, 0, ',', ' ') }} tickets</p>
            <p class="text-sm text-primary-100">{{ \App\Support\Format::gnf($enCirculation->montant) }}</p>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('lots.index') }}" class="card mt-6 grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <input type="hidden" name="tri" value="{{ $tri }}">
        <input type="hidden" name="sens" value="{{ $sens }}">
        <x-text-input name="q" type="search" :value="request('q')" placeholder="Numéro du lot..." />
        <x-select name="site">
            <option value="">Tous les sites</option>
            @foreach ($sites as $site)
                <option value="{{ $site->id }}" @selected(request('site') == $site->id)>{{ $site->nom }}</option>
            @endforeach
        </x-select>
        <x-select name="agent">
            <option value="">Tous les agents</option>
            @foreach ($agents as $agent)
                <option value="{{ $agent->id }}" @selected(request('agent') == $agent->id)>{{ $agent->nom_complet }}</option>
            @endforeach
        </x-select>
        <x-select name="superviseur">
            <option value="">Tous les superviseurs</option>
            @foreach ($superviseurs as $sup)
                <option value="{{ $sup->id }}" @selected(request('superviseur') == $sup->id)>{{ $sup->nom_complet }}</option>
            @endforeach
        </x-select>
        <x-select name="statut">
            <option value="">Tous les statuts</option>
            @foreach (\App\Models\Lot::STATUTS as $valeur => $statut)
                <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $statut['label'] }}</option>
            @endforeach
        </x-select>
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">Du</span>
            <x-text-input name="du" type="date" :value="request('du')" class="w-full" />
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">au</span>
            <x-text-input name="au" type="date" :value="request('au')" class="w-full" />
        </div>
        <div class="flex gap-2">
            <x-primary-button>Filtrer</x-primary-button>
            @if (request()->hasAny($filtres))
                <a href="{{ route('lots.index') }}"><x-secondary-button>Réinitialiser</x-secondary-button></a>
            @endif
        </div>
    </form>

    {{-- Liste --}}
    <div class="card mt-6 overflow-hidden">
        @if ($lots->isEmpty())
            <div class="flex flex-col items-center px-6 py-16 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600"><x-icon name="ticket" class="h-6 w-6" /></span>
                @if (request()->hasAny($filtres))
                    <h3 class="mt-4 font-semibold text-gray-900">Aucun lot ne correspond à votre recherche</h3>
                    <a href="{{ route('lots.index') }}" class="mt-2 text-sm font-medium text-primary-600 hover:text-primary-700">Voir tous les lots</a>
                @else
                    <h3 class="mt-4 font-semibold text-gray-900">Aucun lot pour le moment</h3>
                    <p class="mt-1 text-sm text-gray-500">Enregistrez chaque lot de tickets que vous remettez à un agent.</p>
                    <a href="{{ route('lots.create') }}" class="mt-4"><x-primary-button type="button">+ Nouveau lot</x-primary-button></a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3"><x-sort-link column="code" :tri="$tri" :sens="$sens">Lot</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="date_remise" :tri="$tri" :sens="$sens">Remise</x-sort-link></th>
                            <th class="px-4 py-3">Site</th>
                            <th class="px-4 py-3">Agent</th>
                            <th class="px-4 py-3">Superviseur</th>
                            <th class="px-4 py-3 text-right"><x-sort-link column="quantite_totale" :tri="$tri" :sens="$sens">Tickets</x-sort-link></th>
                            <th class="px-4 py-3 text-right"><x-sort-link column="montant_total" :tri="$tri" :sens="$sens">Montant</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="statut" :tri="$tri" :sens="$sens">Statut</x-sort-link></th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($lots as $lot)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <a href="{{ route('lots.show', $lot) }}" class="font-mono text-xs font-semibold text-gray-900 hover:text-primary-600">{{ $lot->code }}</a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                    {{ $lot->date_remise->format('d/m/Y') }}
                                    @if ($lot->estEnRetard())
                                        <span class="block text-xs text-red-600">Fin prévue dépassée</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $lot->site?->nom }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $lot->agent?->nom_complet }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $lot->superviseur?->nom_complet ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium">{{ number_format($lot->quantite_totale, 0, ',', ' ') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-900">{{ $lot->montant_affiche }}</td>
                                <td class="px-4 py-3"><x-badge :color="$lot->statut_color">{{ $lot->statut_label }}</x-badge></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <x-table-actions
                                        :show="route('lots.show', $lot)"
                                        :edit="$lot->estModifiable() ? route('lots.edit', $lot) : null"
                                        :destroy="$lot->statut !== 'termine' ? route('lots.destroy', $lot) : null"
                                        :name="'supprimer-lot-'.$lot->id"
                                        :message="'Le lot '.$lot->code.' ('.$lot->quantite_totale.' tickets) sera supprimé.'" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($lots->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">{{ $lots->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
