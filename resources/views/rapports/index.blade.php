@php use App\Support\Format; @endphp
<x-app-layout>
    <x-slot name="title">Rapports de lot</x-slot>
    <x-slot name="header">Rapports de fin de lot</x-slot>
    <x-slot name="actions">
        <a href="{{ route('lots.index', ['statut' => 'en_cours']) }}">
            <x-primary-button type="button">Lots en attente de rapport ({{ $lotsEnAttente }})</x-primary-button>
        </a>
    </x-slot>

    @php
        $filtres = ['q', 'site', 'agent', 'ecart', 'du', 'au'];
        $remis = $totaux->vendus + $totaux->defectueux + $totaux->rendus;
        $tauxDefectueux = $remis ? round($totaux->defectueux * 100 / $remis, 1) : 0;
    @endphp

    {{-- Totaux selon les filtres --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="card p-4">
            <p class="text-sm text-gray-500">Rapports</p>
            <p class="mt-1 text-2xl font-bold">{{ $totaux->rapports }}</p>
        </div>
        <div class="card p-4">
            <p class="text-sm text-gray-500">Tickets vendus</p>
            <p class="mt-1 text-2xl font-bold text-green-700">{{ number_format($totaux->vendus, 0, ',', ' ') }}</p>
        </div>
        <div class="card p-4">
            <p class="text-sm text-gray-500">Défectueux</p>
            <p class="mt-1 text-2xl font-bold text-red-600">{{ number_format($totaux->defectueux, 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-500">{{ str_replace('.', ',', $tauxDefectueux) }} % des tickets remis</p>
        </div>
        <div class="card bg-primary-600 p-4 text-white">
            <p class="text-sm text-primary-100">Montant vendu</p>
            <p class="mt-1 text-xl font-bold">{{ Format::gnf($totaux->montant_vendu) }}</p>
            <p class="text-xs text-primary-100">dont {{ Format::gnf($totaux->commissions) }} de commissions</p>
        </div>
        <a href="{{ route('rapports.index', ['ecart' => 'manquant']) }}" class="card p-4 hover:border-red-300 {{ request('ecart') === 'manquant' ? 'ring-2 ring-red-500' : '' }}">
            <p class="text-sm text-gray-500">Manquants</p>
            <p class="mt-1 text-xl font-bold {{ $totaux->manquants ? 'text-red-600' : 'text-gray-900' }}">{{ Format::gnf($totaux->manquants) }}</p>
        </a>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('rapports.index') }}" class="card mt-6 grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <input type="hidden" name="tri" value="{{ $tri }}">
        <input type="hidden" name="sens" value="{{ $sens }}">
        <x-text-input name="q" type="search" :value="request('q')" placeholder="N° de rapport ou de lot..." />
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
        <x-select name="ecart">
            <option value="">Tous les écarts</option>
            <option value="manquant" @selected(request('ecart') === 'manquant')>Avec manquant</option>
        </x-select>
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">Du</span>
            <x-text-input name="du" type="date" :value="request('du')" class="w-full" />
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">au</span>
            <x-text-input name="au" type="date" :value="request('au')" class="w-full" />
        </div>
        <div class="flex gap-2 lg:col-span-2">
            <x-primary-button>Filtrer</x-primary-button>
            @if (request()->hasAny($filtres))
                <a href="{{ route('rapports.index') }}"><x-secondary-button>Réinitialiser</x-secondary-button></a>
            @endif
        </div>
    </form>

    {{-- Liste --}}
    <div class="card mt-6 overflow-hidden">
        @if ($rapports->isEmpty())
            <div class="flex flex-col items-center px-6 py-16 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600"><x-icon name="clipboard" class="h-6 w-6" /></span>
                @if (request()->hasAny($filtres))
                    <h3 class="mt-4 font-semibold text-gray-900">Aucun rapport ne correspond à votre recherche</h3>
                    <a href="{{ route('rapports.index') }}" class="mt-2 text-sm font-medium text-primary-600 hover:text-primary-700">Voir tous les rapports</a>
                @else
                    <h3 class="mt-4 font-semibold text-gray-900">Aucun rapport pour le moment</h3>
                    <p class="mt-1 text-sm text-gray-500">Ouvrez un lot en cours et cliquez sur « Saisir le rapport » quand l'agent a fini.</p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3"><x-sort-link column="code" :tri="$tri" :sens="$sens">Rapport</x-sort-link></th>
                            <th class="px-4 py-3"><x-sort-link column="date_rapport" :tri="$tri" :sens="$sens">Date</x-sort-link></th>
                            <th class="px-4 py-3">Lot / Site</th>
                            <th class="px-4 py-3">Agent</th>
                            <th class="px-4 py-3 text-right"><x-sort-link column="quantite_vendue" :tri="$tri" :sens="$sens">Vendus</x-sort-link></th>
                            <th class="px-4 py-3 text-right"><x-sort-link column="quantite_defectueuse" :tri="$tri" :sens="$sens">Défect.</x-sort-link></th>
                            <th class="px-4 py-3 text-right"><x-sort-link column="montant_vendu" :tri="$tri" :sens="$sens">Montant vendu</x-sort-link></th>
                            <th class="px-4 py-3 text-right"><x-sort-link column="ecart" :tri="$tri" :sens="$sens">Écart</x-sort-link></th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($rapports as $rapport)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3"><a href="{{ route('rapports.show', $rapport) }}" class="font-mono text-xs font-semibold text-gray-900 hover:text-primary-600">{{ $rapport->code }}</a></td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $rapport->date_rapport->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('lots.show', $rapport->lot) }}" class="font-mono text-xs text-gray-500 hover:text-primary-600">{{ $rapport->lot->code }}</a>
                                    <span class="block text-gray-700">{{ $rapport->lot->site?->nom }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $rapport->lot->agent?->nom_complet }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-green-700">{{ $rapport->quantite_vendue }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right {{ $rapport->quantite_defectueuse ? 'font-medium text-red-600' : 'text-gray-400' }}">{{ $rapport->quantite_defectueuse }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-900">{{ Format::gnf($rapport->montant_vendu) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right"><x-badge :color="$rapport->ecart_color">{{ $rapport->ecart === 0 ? 'OK' : ($rapport->ecart < 0 ? '−' : '+') . Format::gnf(abs($rapport->ecart)) }}</x-badge></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <x-table-actions
                                        :show="route('rapports.show', $rapport)"
                                        :edit="route('rapports.edit', $rapport)"
                                        :destroy="route('rapports.destroy', $rapport)"
                                        :name="'supprimer-rapport-'.$rapport->id"
                                        :message="'Le rapport '.$rapport->code.' sera supprimé et le lot '.$rapport->lot->code.' repassera « en cours ».'" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($rapports->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">{{ $rapports->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
