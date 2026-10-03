@php use App\Support\Format; @endphp
<x-app-layout>
    <x-slot name="title">Tableau de bord</x-slot>
    <x-slot name="header">Tableau de bord</x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Bonjour {{ Auth::user()->name }} 👋</h2>
        <p class="text-sm text-gray-500">Voici l'état de votre réseau de hotspots.</p>
    </div>

    @php
        $tauxDefectueux = $mois->remis ? round($mois->defectueux * 100 / $mois->remis, 1) : 0;
        $kpis = [
            ['label' => 'Ventes du mois', 'value' => Format::gnf($mois->ventes), 'aide' => number_format($mois->vendus, 0, ',', ' ') . ' tickets vendus', 'icon' => 'banknotes', 'fort' => true],
            ['label' => 'Commissions du mois', 'value' => Format::gnf($mois->commissions), 'aide' => Format::gnf($commissionsAPayer) . ' restent à payer', 'icon' => 'clipboard'],
            ['label' => 'Dépenses du mois', 'value' => Format::gnf($depensesMois), 'aide' => 'tous sites confondus', 'icon' => 'receipt'],
            ['label' => 'Bénéfice du mois', 'value' => Format::gnf($mois->ventes - $mois->commissions - $depensesMois), 'aide' => 'ventes − commissions − dépenses', 'icon' => 'chart'],
            ['label' => 'Tickets défectueux', 'value' => str_replace('.', ',', $tauxDefectueux) . ' %', 'aide' => $mois->defectueux . ' ce mois-ci', 'icon' => 'alert'],
            ['label' => 'Manquants du mois', 'value' => Format::gnf($mois->manquants), 'aide' => 'argent non versé', 'icon' => 'banknotes'],
        ];
        $reseau = [
            ['label' => 'Sites actifs', 'value' => $sitesActifs, 'icon' => 'wifi', 'route' => 'sites.index'],
            ['label' => 'Superviseurs actifs', 'value' => $superviseursActifs, 'icon' => 'user-tie', 'route' => 'superviseurs.index'],
            ['label' => 'Agents actifs', 'value' => $agentsActifs, 'icon' => 'users', 'route' => 'agents.index'],
            ['label' => 'Lots en cours', 'value' => $lotsEnCours . ' · ' . number_format($ticketsEnCirculation, 0, ',', ' ') . ' tickets', 'icon' => 'ticket', 'route' => 'lots.index'],
        ];
    @endphp

    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ ucfirst(now()->translatedFormat('F Y')) }}</h3>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($kpis as $kpi)
            <div @class(['card p-5', 'bg-primary-600 text-white' => $kpi['fort'] ?? false])>
                <div class="flex items-center justify-between">
                    <p @class(['text-sm font-medium', 'text-primary-100' => $kpi['fort'] ?? false, 'text-gray-500' => ! ($kpi['fort'] ?? false)])>{{ $kpi['label'] }}</p>
                    <span @class(['flex h-9 w-9 items-center justify-center rounded-lg', 'bg-white/15' => $kpi['fort'] ?? false, 'bg-primary-50 text-primary-600' => ! ($kpi['fort'] ?? false)])>
                        <x-icon :name="$kpi['icon']" class="h-5 w-5" />
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold">{{ $kpi['value'] }}</p>
                <p @class(['text-xs', 'text-primary-100' => $kpi['fort'] ?? false, 'text-gray-500' => ! ($kpi['fort'] ?? false)])>{{ $kpi['aide'] }}</p>
            </div>
        @endforeach
    </div>

    <h3 class="mb-3 mt-8 text-sm font-semibold uppercase tracking-wide text-gray-500">Réseau</h3>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($reseau as $kpi)
            <a href="{{ route($kpi['route']) }}" class="card p-5 hover:border-primary-300">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500">{{ $kpi['label'] }}</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><x-icon :name="$kpi['icon']" class="h-5 w-5" /></span>
                </div>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ $kpi['value'] }}</p>
            </a>
        @endforeach
    </div>

    {{-- Derniers rapports --}}
    <div class="card mt-8 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4">
            <h3 class="font-semibold text-gray-900">Derniers rapports de lot</h3>
            <a href="{{ route('rapports.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">Tout voir</a>
        </div>
        @if ($derniersRapports->isEmpty())
            <p class="border-t border-gray-100 px-6 py-6 text-sm text-gray-500">Aucun rapport saisi pour le moment.</p>
        @else
            <table class="min-w-full divide-y divide-gray-100 border-t border-gray-100 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @foreach ($derniersRapports as $rapport)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-3"><a href="{{ route('rapports.show', $rapport) }}" class="font-mono text-xs font-semibold hover:text-primary-600">{{ $rapport->code }}</a></td>
                            <td class="whitespace-nowrap px-6 py-3 text-gray-600">{{ $rapport->date_rapport->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-gray-700">{{ $rapport->lot->site?->nom }}</td>
                            <td class="px-6 py-3 text-gray-700">{{ $rapport->lot->agent?->nom_complet }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right font-semibold">{{ Format::gnf($rapport->montant_vendu) }}</td>
                            <td class="px-6 py-3 text-right"><x-badge :color="$rapport->ecart_color">{{ $rapport->ecart_label }}</x-badge></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-app-layout>
