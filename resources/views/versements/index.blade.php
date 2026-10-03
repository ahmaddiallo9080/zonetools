@php use App\Support\Format; @endphp
<x-app-layout>
    <x-slot name="title">Versements & commissions</x-slot>
    <x-slot name="header">Versements & commissions</x-slot>
    <x-slot name="actions">
        <a href="{{ route('paiements.index') }}"><x-secondary-button>Historique des paiements</x-secondary-button></a>
        <a href="{{ route('paiements.create') }}"><x-primary-button type="button"><span class="text-lg leading-none">+</span> Nouveau paiement</x-primary-button></a>
    </x-slot>

    {{-- Totaux --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card bg-primary-600 p-4 text-white">
            <p class="text-sm text-primary-100">À payer aux superviseurs</p>
            <p class="mt-1 text-xl font-bold">{{ Format::gnf($totaux['superviseurs']) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-sm text-gray-500">À payer aux agents</p>
            <p class="mt-1 text-xl font-bold">{{ Format::gnf($totaux['agents']) }}</p>
            @if (config('zonetools.commission_agent_deduite'))
                <p class="text-xs text-gray-500">Les agents gardent leur commission sur les ventes</p>
            @endif
        </div>
        <div class="card p-4">
            <p class="text-sm text-gray-500">Payé ce mois-ci</p>
            <p class="mt-1 text-xl font-bold text-green-700">{{ Format::gnf($totaux['payeMois']) }}</p>
        </div>
        <a href="{{ route('rapports.index', ['ecart' => 'manquant']) }}" class="card p-4 hover:border-red-300">
            <p class="text-sm text-gray-500">Manquants des agents</p>
            <p class="mt-1 text-xl font-bold {{ $totaux['manquants'] ? 'text-red-600' : '' }}">{{ Format::gnf($totaux['manquants']) }}</p>
            <p class="text-xs text-gray-500">Argent non versé (suivi)</p>
        </a>
    </div>

    {{-- Onglets --}}
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm font-medium">
            @foreach (['superviseurs' => 'Superviseurs', 'agents' => 'Agents'] as $cle => $label)
                <a href="{{ route('versements.index', ['onglet' => $cle] + request()->only('filtre')) }}"
                   @class(['rounded-md px-4 py-1.5', 'bg-primary-600 text-white' => $onglet === $cle, 'text-gray-600 hover:text-gray-900' => $onglet !== $cle])>{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="onglet" value="{{ $onglet }}">
            <x-text-input name="q" type="search" :value="request('q')" placeholder="Rechercher un nom..." class="w-56" />
            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="filtre" value="a_payer" onchange="this.form.submit()" @checked(request('filtre') === 'a_payer') class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                Seulement à payer
            </label>
        </form>
    </div>

    {{-- Soldes --}}
    <div class="card mt-4 overflow-hidden">
        @if ($lignes->isEmpty())
            <p class="px-6 py-12 text-center text-sm text-gray-500">Aucune commission à afficher.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">{{ $onglet === 'agents' ? 'Agent' : 'Superviseur' }}</th>
                            <th class="px-4 py-3 text-right">Rapports</th>
                            <th class="px-4 py-3 text-right">Commissions gagnées</th>
                            @if ($onglet === 'agents')
                                <th class="px-4 py-3 text-right">Déjà gardées</th>
                            @endif
                            <th class="px-4 py-3 text-right">Déjà payé</th>
                            <th class="px-4 py-3 text-right">Reste à payer</th>
                            @if ($onglet === 'agents')
                                <th class="px-4 py-3 text-right">Manquants</th>
                            @endif
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($lignes as $ligne)
                            @php
                                $p = $ligne->personne;
                                $type = $onglet === 'agents' ? 'agent' : 'superviseur';
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route($onglet.'.show', $p) }}" class="flex items-center gap-3">
                                        <x-avatar :initiales="$p->initiales" size="sm" />
                                        <span>
                                            <span class="block font-medium text-gray-900 hover:text-primary-600">{{ $p->nom_complet }}</span>
                                            <span class="block font-mono text-xs text-gray-400">{{ $p->code }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $ligne->rapports }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">{{ Format::gnf($ligne->gagnees) }}</td>
                                @if ($onglet === 'agents')
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-gray-500">{{ Format::gnf($ligne->gardees) }}</td>
                                @endif
                                <td class="whitespace-nowrap px-4 py-3 text-right text-green-700">{{ Format::gnf($ligne->payees) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <span @class(['font-bold', 'text-primary-700' => $ligne->solde > 0, 'text-gray-400' => $ligne->solde === 0, 'text-amber-700' => $ligne->solde < 0])>
                                        {{ Format::gnf($ligne->solde) }}
                                    </span>
                                    @if ($ligne->solde < 0)<span class="block text-xs text-amber-700">avance versée</span>@endif
                                </td>
                                @if ($onglet === 'agents')
                                    <td class="whitespace-nowrap px-4 py-3 text-right {{ $ligne->manquants ? 'font-medium text-red-600' : 'text-gray-400' }}">{{ Format::gnf($ligne->manquants) }}</td>
                                @endif
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('paiements.index', ['beneficiaire' => "$type:{$p->id}"]) }}" title="Historique des paiements" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-primary-600">
                                            <x-icon name="clipboard" class="h-5 w-5" />
                                        </a>
                                        @if ($ligne->solde > 0)
                                            <a href="{{ route('paiements.create', ['beneficiaire' => "$type:{$p->id}"]) }}" title="Payer" class="rounded-lg p-1.5 text-green-600 hover:bg-green-50 hover:text-green-700">
                                                <x-icon name="banknotes" class="h-5 w-5" />
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Derniers paiements --}}
    <div class="card mt-6 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4">
            <h3 class="font-semibold text-gray-900">Derniers paiements</h3>
            <a href="{{ route('paiements.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">Tout voir</a>
        </div>
        @if ($derniersPaiements->isEmpty())
            <p class="border-t border-gray-100 px-6 py-6 text-sm text-gray-500">Aucun paiement enregistré.</p>
        @else
            @include('paiements._tableau', ['paiements' => $derniersPaiements, 'compact' => true])
        @endif
    </div>
</x-app-layout>
