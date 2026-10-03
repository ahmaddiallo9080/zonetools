@php use App\Support\Format; $t = $reseau['total']; @endphp
<x-app-layout>
    <x-slot name="title">Bilans mensuels</x-slot>
    <x-slot name="header">Bilans mensuels</x-slot>
    <x-slot name="actions">
        <a href="{{ route('bilans.export', $mois->format('Y-m')) }}"><x-secondary-button>Exporter (Excel)</x-secondary-button></a>
        <a href="{{ route('bilans.global', $mois->format('Y-m')) }}" target="_blank"><x-primary-button type="button">Bilan global imprimable</x-primary-button></a>
    </x-slot>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ ucfirst($mois->translatedFormat('F Y')) }}</h2>
            @php $clotures = $reseau['sites']->filter(fn ($l) => $l['bilan'])->count(); @endphp
            <p class="text-sm text-gray-500">
                {{ $clotures }} / {{ $reseau['sites']->count() }} site(s) clôturé(s)
                @unless ($moisTermine) · <span class="text-amber-700">mois en cours, chiffres provisoires</span> @endunless
            </p>
        </div>
        @include('bilans._navigation-mois', ['route' => 'bilans.index'])
    </div>

    {{-- Totaux du réseau --}}
    @php
        $cartes = [
            ['Ventes', $t['ventes'], $precedent['ventes'], false, true],
            ['Commissions', $t['commission_agents'] + $t['commission_superviseur'], $precedent['commission_agents'] + $precedent['commission_superviseur'], true, false],
            ['Dépenses', $t['depenses'], $precedent['depenses'], true, false],
            ['Bénéfice net', $t['benefice'], $precedent['benefice'], false, false],
        ];
    @endphp
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cartes as [$label, $valeur, $avant, $inverse, $fort])
            <div @class(['card p-5', 'bg-primary-600 text-white' => $fort])>
                <p @class(['text-sm', 'text-primary-100' => $fort, 'text-gray-500' => ! $fort])>{{ $label }}</p>
                <p @class(['mt-1 text-2xl font-bold tabular-nums', 'text-red-600' => ! $fort && $valeur < 0, 'text-gray-900' => ! $fort && $valeur >= 0])>{{ Format::gnf($valeur) }}</p>
                @unless ($fort)
                    @include('bilans._evolution', ['actuel' => $valeur, 'avant' => $avant, 'inverse' => $inverse])
                @else
                    <p class="text-xs text-primary-100">{{ number_format($t['vendus'], 0, ',', ' ') }} tickets vendus</p>
                @endunless
            </div>
        @endforeach
    </div>

    {{-- Tableau des sites --}}
    <div class="card mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-3 py-3">Site</th>
                        <th class="px-3 py-3">Bilan</th>
                        <th class="px-3 py-3 text-right">Vendus</th>
                        <th class="px-3 py-3 text-right">Défect.</th>
                        <th class="px-3 py-3 text-right">Ventes</th>
                        <th class="px-3 py-3 text-right">Commissions</th>
                        <th class="px-3 py-3 text-right">Dépenses</th>
                        <th class="px-3 py-3 text-right">Bénéfice</th>
                        <th class="px-3 py-3 text-right">Manquants</th>
                        <th class="px-3 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($reseau['sites'] as $l)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-3">
                                <a href="{{ route('bilans.show', [$mois->format('Y-m'), $l['site']]) }}" class="font-medium text-gray-900 hover:text-primary-600">{{ $l['site']->nom }}</a>
                            </td>
                            <td class="px-3 py-3">
                                @if ($l['bilan'])
                                    <x-badge color="green">Clôturé</x-badge>
                                @elseif ($moisTermine)
                                    <x-badge color="amber">À clôturer</x-badge>
                                @else
                                    <x-badge color="gray">En cours</x-badge>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums">{{ number_format($l['vendus'], 0, ',', ' ') }}</td>
                            <td class="px-3 py-3 text-right tabular-nums {{ $l['defectueux'] ? 'text-red-600' : 'text-gray-400' }}">{{ $l['defectueux'] }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right font-semibold tabular-nums">{{ Format::gnf($l['ventes']) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums text-gray-600">{{ Format::gnf($l['commission_agents'] + $l['commission_superviseur']) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums text-gray-600">{{ Format::gnf($l['depenses']) }}</td>
                            <td @class(['whitespace-nowrap px-3 py-3 text-right font-semibold tabular-nums', 'text-red-600' => $l['benefice'] < 0])>{{ Format::gnf($l['benefice']) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums {{ $l['manquants'] ? 'text-red-600' : 'text-gray-400' }}">{{ Format::gnf($l['manquants']) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                <x-table-actions :show="route('bilans.show', [$mois->format('Y-m'), $l['site']])" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-12 text-center text-gray-500">Aucun site.</td></tr>
                    @endforelse

                    @if ($reseau['generales']['depenses'])
                        <tr class="bg-gray-50/60">
                            <td class="px-3 py-3 italic text-gray-600" colspan="6">Dépenses générales (tous les sites)</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums text-gray-600">{{ Format::gnf($reseau['generales']['depenses']) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right font-semibold tabular-nums text-red-600">{{ Format::gnf(-$reseau['generales']['depenses']) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    @endif
                </tbody>
                <tfoot class="border-t-2 border-gray-200 bg-gray-50 font-semibold">
                    <tr>
                        <td class="px-3 py-3" colspan="2">Total réseau</td>
                        <td class="px-3 py-3 text-right tabular-nums">{{ number_format($t['vendus'], 0, ',', ' ') }}</td>
                        <td class="px-3 py-3 text-right tabular-nums">{{ $t['defectueux'] }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums text-primary-700">{{ Format::gnf($t['ventes']) }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums">{{ Format::gnf($t['commission_agents'] + $t['commission_superviseur']) }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums">{{ Format::gnf($t['depenses']) }}</td>
                        <td @class(['whitespace-nowrap px-3 py-3 text-right tabular-nums', 'text-red-600' => $t['benefice'] < 0])>{{ Format::gnf($t['benefice']) }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums">{{ Format::gnf($t['manquants']) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <p class="mt-4 text-xs text-gray-500">
        Ventes, commissions et manquants : rapports de fin de lot datés dans le mois. Dépenses : dépenses datées dans le mois.
        Un bilan clôturé garde les chiffres du jour de sa clôture.
    </p>
</x-app-layout>
