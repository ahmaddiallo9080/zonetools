@php
    use App\Support\Format;
    $pc = fn ($v) => str_replace('.', ',', $v) . ' %';
@endphp
<x-app-layout>
    <x-slot name="title">Statistiques</x-slot>
    <x-slot name="header">Statistiques</x-slot>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('statistiques.index') }}" x-data="{ periode: @js($periode) }"
          class="card flex flex-col gap-3 p-4 sm:flex-row sm:flex-wrap sm:items-center">
        <x-select name="periode" x-model="periode" @change="if (periode !== 'perso') $el.form.submit()" class="sm:w-52">
            @foreach (\App\Http\Controllers\StatistiqueController::PERIODES as $cle => $label)
                <option value="{{ $cle }}" @selected($periode === $cle)>{{ $label }}</option>
            @endforeach
        </x-select>
        <div class="flex items-center gap-2" x-show="periode === 'perso'" x-cloak>
            <span class="text-sm text-gray-500">Du</span>
            <x-text-input type="date" name="du" :value="$du->format('Y-m-d')" />
            <span class="text-sm text-gray-500">au</span>
            <x-text-input type="date" name="au" :value="$au->format('Y-m-d')" />
        </div>
        <x-select name="site" onchange="this.form.submit()" class="sm:w-56">
            <option value="">Tous les sites</option>
            @foreach ($sites as $site)
                <option value="{{ $site->id }}" @selected($siteId === $site->id)>{{ $site->nom }}</option>
            @endforeach
        </x-select>
        <x-primary-button>Afficher</x-primary-button>
        <p class="text-sm text-gray-500 sm:ms-auto">
            Du <span class="font-medium text-gray-700">{{ $du->format('d/m/Y') }}</span>
            au <span class="font-medium text-gray-700">{{ $au->format('d/m/Y') }}</span>
            · {{ $kpis->rapports }} rapport(s)
        </p>
    </form>

    @if ($kpis->rapports === 0 && $kpis->depenses === 0)
        <div class="card mt-6 flex flex-col items-center px-6 py-16 text-center">
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600"><x-icon name="chart" class="h-6 w-6" /></span>
            <h3 class="mt-4 font-semibold text-gray-900">Aucun rapport sur cette période</h3>
            <p class="mt-1 text-sm text-gray-500">Les statistiques sont calculées à partir des rapports de fin de lot.</p>
        </div>
    @else
        {{-- Indicateurs --}}
        @php
            $cartes = [
                ['Ventes', Format::gnf($kpis->ventes), number_format($kpis->vendus, 0, ',', ' ') . ' tickets vendus (' . $pc($kpis->tauxVente) . ' des remis)', true],
                ['Commissions', Format::gnf($kpis->commissions), 'agents + superviseurs', false],
                ['Dépenses', Format::gnf($kpis->depenses), 'internet, électricité, carburant...', false],
                ['Bénéfice net', Format::gnf($kpis->benefice), 'ventes − commissions − dépenses', false],
                ['Tickets défectueux', $pc($kpis->tauxDefectueux), number_format($kpis->defectueux, 0, ',', ' ') . ' tickets', false],
                ['Manquants', Format::gnf($kpis->manquants), 'argent non versé par les agents', false],
            ];
        @endphp
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($cartes as [$label, $valeur, $aide, $fort])
                <div @class(['card p-4', 'bg-primary-600 text-white' => $fort])>
                    <p @class(['text-sm', 'text-primary-100' => $fort, 'text-gray-500' => ! $fort])>{{ $label }}</p>
                    <p @class(['mt-1 text-xl font-bold tabular-nums', 'text-gray-900' => ! $fort, 'text-red-600' => $label === 'Bénéfice net' && $kpis->benefice < 0])>{{ $valeur }}</p>
                    <p @class(['text-xs', 'text-primary-100' => $fort, 'text-gray-500' => ! $fort])>{{ $aide }}</p>
                </div>
            @endforeach
        </div>

        {{-- Évolution --}}
        <div class="card mt-6 p-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h3 class="font-semibold text-gray-900">Évolution des ventes</h3>
                <p class="text-sm text-gray-500">Montant vendu par {{ $evolution['parJour'] ? 'jour' : 'mois' }} (date du rapport)</p>
            </div>
            <div class="mt-6">
                <x-graph.colonnes :points="$evolution['points']" />
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Par site --}}
            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">Ventes par site</h3>
                <div class="mt-5">
                    <x-graph.barres :lignes="$parSite->take(10)->map(fn ($l) => [
                        'label' => $l->entite->nom,
                        'valeur' => $l->ventes,
                        'affiche' => Format::gnf($l->ventes),
                        'detail' => number_format($l->vendus, 0, ',', ' ') . ' tickets',
                        'lien' => route('sites.show', $l->entite),
                    ])->all()" />
                </div>
            </div>

            {{-- Par forfait --}}
            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">Ventes par forfait</h3>
                <div class="mt-5">
                    <x-graph.barres :lignes="$parForfait->map(fn ($l) => [
                        'label' => $l->entite->nom,
                        'valeur' => $l->ventes,
                        'affiche' => Format::gnf($l->ventes),
                        'detail' => number_format($l->vendus, 0, ',', ' ') . ' tickets',
                        'lien' => route('forfaits.show', $l->entite),
                    ])->all()" />
                </div>
            </div>

            {{-- Défectueux par site --}}
            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">Taux de tickets défectueux par site</h3>
                <p class="text-sm text-gray-500">Part des tickets remis qui n'ont pas fonctionné</p>
                <div class="mt-5">
                    <x-graph.barres couleur="bg-amber-500" :lignes="$parSite->sortByDesc('tauxDefectueux')->take(10)->map(fn ($l) => [
                        'label' => $l->entite->nom,
                        'valeur' => $l->tauxDefectueux,
                        'affiche' => $pc($l->tauxDefectueux),
                        'detail' => $l->defectueux . ' défectueux',
                        'lien' => route('sites.show', $l->entite),
                    ])->values()->all()" />
                </div>
            </div>

            {{-- Superviseurs --}}
            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">Ventes par superviseur</h3>
                <div class="mt-5">
                    <x-graph.barres :lignes="$parSuperviseur->map(fn ($l) => [
                        'label' => $l->entite->nom_complet,
                        'valeur' => $l->ventes,
                        'affiche' => Format::gnf($l->ventes),
                        'detail' => $l->rapports . ' rapport(s)',
                        'lien' => route('superviseurs.show', $l->entite),
                    ])->all()" vide="Aucun lot supervisé sur la période." />
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Bénéfice par site --}}
            <div class="card overflow-hidden">
                <div class="px-6 py-4">
                    <h3 class="font-semibold text-gray-900">Bénéfice par site</h3>
                    <p class="text-sm text-gray-500">Net gérant (ventes − commissions) moins les dépenses du site</p>
                </div>
                @if ($beneficeParSite->isEmpty())
                    <p class="border-t border-gray-100 px-6 py-6 text-sm text-gray-500">Aucune donnée sur la période.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 border-t border-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr><th class="px-3 py-2">Site</th><th class="px-3 py-2 text-right">Net</th><th class="px-3 py-2 text-right">Dépenses</th><th class="px-3 py-2 text-right">Bénéfice</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($beneficeParSite as $ligne)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2.5"><a href="{{ route('sites.show', $ligne->entite) }}" class="font-medium text-gray-900 hover:text-primary-600">{{ $ligne->entite->nom }}</a></td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums text-gray-600">{{ Format::gnf($ligne->net) }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums text-gray-600">{{ Format::gnf($ligne->depenses) }}</td>
                                        <td @class(['whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums', 'text-gray-900' => $ligne->benefice >= 0, 'text-red-600' => $ligne->benefice < 0])>{{ Format::gnf($ligne->benefice) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Dépenses par catégorie --}}
            <div class="card p-6">
                <div class="flex items-baseline justify-between">
                    <h3 class="font-semibold text-gray-900">Dépenses par catégorie</h3>
                    <a href="{{ route('depenses.index', array_filter(['du' => $du->format('Y-m-d'), 'au' => $au->format('Y-m-d'), 'site' => $siteId])) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">Voir le détail</a>
                </div>
                <div class="mt-5">
                    <x-graph.barres :lignes="$depensesParCategorie->map(fn ($montant, $cat) => [
                        'label' => \App\Models\Depense::CATEGORIES[$cat] ?? $cat,
                        'valeur' => (int) $montant,
                        'affiche' => Format::gnf($montant),
                    ])->values()->all()" vide="Aucune dépense sur la période." />
                </div>
            </div>
        </div>

        {{-- Classement des agents --}}
        <div class="card mt-6 overflow-hidden">
            <div class="px-6 py-4">
                <h3 class="font-semibold text-gray-900">Classement des agents</h3>
                <p class="text-sm text-gray-500">Trié par montant vendu sur la période</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border-t border-gray-100 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="w-12 px-4 py-3 text-center">#</th>
                            <th class="px-4 py-3">Agent</th>
                            <th class="px-4 py-3">Site</th>
                            <th class="px-4 py-3 text-right">Rapports</th>
                            <th class="px-4 py-3 text-right">Vendus</th>
                            <th class="px-4 py-3 text-right">Taux de vente</th>
                            <th class="px-4 py-3 text-right">Défectueux</th>
                            <th class="px-4 py-3 text-right">Manquants</th>
                            <th class="w-64 px-4 py-3">Montant vendu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @php $maxAgent = max(1, $parAgent->max('ventes')); @endphp
                        @foreach ($parAgent as $i => $ligne)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-center">
                                    <span @class(['inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold',
                                        'bg-primary-600 text-white' => $i === 0, 'bg-primary-100 text-primary-700' => $i > 0 && $i < 3, 'text-gray-500' => $i >= 3])>{{ $i + 1 }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('agents.show', $ligne->entite) }}" class="flex items-center gap-3">
                                        <x-avatar :initiales="$ligne->entite->initiales" size="sm" />
                                        <span class="font-medium text-gray-900 hover:text-primary-600">{{ $ligne->entite->nom_complet }}</span>
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $ligne->entite->site?->nom ?? '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-600">{{ $ligne->rapports }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($ligne->vendus, 0, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $pc($ligne->tauxVente) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $pc($ligne->tauxDefectueux) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums {{ $ligne->manquants ? 'font-medium text-red-600' : 'text-gray-400' }}">{{ Format::gnf($ligne->manquants) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 flex-1 rounded-full bg-gray-100">
                                            <div class="h-2 rounded-full bg-primary-600" style="width: {{ $ligne->ventes / $maxAgent * 100 }}%"></div>
                                        </div>
                                        <span class="w-28 shrink-0 text-right font-semibold tabular-nums text-gray-900">{{ Format::gnf($ligne->ventes) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-app-layout>
