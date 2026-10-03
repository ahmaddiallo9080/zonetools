@php use App\Support\Format; $t = $reseau['total']; @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bilan global {{ $mois->translatedFormat('F Y') }} · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white font-sans text-gray-900 antialiased">
    <div class="mx-auto max-w-5xl p-8">
        <div class="no-print mb-6 flex justify-end gap-3">
            <a href="{{ route('bilans.index', ['mois' => $mois->format('Y-m')]) }}"><x-secondary-button>Retour</x-secondary-button></a>
            <x-primary-button type="button" onclick="window.print()">Imprimer / PDF</x-primary-button>
        </div>

        <div class="flex items-start justify-between border-b border-gray-200 pb-6">
            <div class="flex items-center gap-3">
                <x-application-logo class="h-12 w-auto text-primary-600" />
                <div>
                    <x-logo-texte class="h-5 w-auto text-gray-900" />
                    <p class="mt-1 text-sm text-gray-500">Bilan global du réseau</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xl font-bold">{{ ucfirst($mois->translatedFormat('F Y')) }}</p>
                <p class="text-sm text-gray-500">Édité le {{ now()->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-4 gap-4">
            @foreach ([['Ventes', $t['ventes']], ['Commissions', $t['commission_agents'] + $t['commission_superviseur']], ['Dépenses', $t['depenses']], ['Bénéfice net', $t['benefice']]] as [$label, $valeur])
                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ $label }}</p>
                    <p @class(['mt-1 text-lg font-bold tabular-nums', 'text-red-600' => $valeur < 0])>{{ Format::gnf($valeur) }}</p>
                </div>
            @endforeach
        </div>

        <table class="mt-8 min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-3 py-2">Site</th><th class="px-3 py-2 text-right">Vendus</th><th class="px-3 py-2 text-right">Défect.</th>
                    <th class="px-3 py-2 text-right">Ventes</th><th class="px-3 py-2 text-right">Commissions</th><th class="px-3 py-2 text-right">Dépenses</th>
                    <th class="px-3 py-2 text-right">Bénéfice</th><th class="px-3 py-2 text-right">Manquants</th><th class="px-3 py-2">Bilan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($reseau['sites'] as $l)
                    <tr>
                        <td class="px-3 py-2 font-medium">{{ $l['site']->nom }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($l['vendus'], 0, ',', ' ') }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $l['defectueux'] }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums">{{ Format::gnf($l['ventes']) }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums">{{ Format::gnf($l['commission_agents'] + $l['commission_superviseur']) }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums">{{ Format::gnf($l['depenses']) }}</td>
                        <td @class(['whitespace-nowrap px-3 py-2 text-right font-semibold tabular-nums', 'text-red-600' => $l['benefice'] < 0])>{{ Format::gnf($l['benefice']) }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums">{{ Format::gnf($l['manquants']) }}</td>
                        <td class="px-3 py-2 text-xs">{{ $l['bilan'] ? 'Clôturé' : 'Provisoire' }}</td>
                    </tr>
                @endforeach
                @if ($reseau['generales']['depenses'])
                    <tr>
                        <td class="px-3 py-2 italic text-gray-600" colspan="5">Dépenses générales (tous les sites)</td>
                        <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums">{{ Format::gnf($reseau['generales']['depenses']) }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-right font-semibold tabular-nums text-red-600">{{ Format::gnf(-$reseau['generales']['depenses']) }}</td>
                        <td colspan="2"></td>
                    </tr>
                @endif
            </tbody>
            <tfoot class="border-t-2 border-gray-300 font-semibold">
                <tr>
                    <td class="px-3 py-2">Total</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format($t['vendus'], 0, ',', ' ') }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ $t['defectueux'] }}</td>
                    <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums">{{ Format::gnf($t['ventes']) }}</td>
                    <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums">{{ Format::gnf($t['commission_agents'] + $t['commission_superviseur']) }}</td>
                    <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums">{{ Format::gnf($t['depenses']) }}</td>
                    <td @class(['whitespace-nowrap px-3 py-2 text-right tabular-nums', 'text-red-600' => $t['benefice'] < 0])>{{ Format::gnf($t['benefice']) }}</td>
                    <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums">{{ Format::gnf($t['manquants']) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        @php $observations = $reseau['sites']->filter(fn ($l) => $l['bilan']?->observations); @endphp
        @if ($observations->isNotEmpty())
            <h3 class="mt-10 font-semibold">Observations par site</h3>
            <div class="mt-3 space-y-3 text-sm">
                @foreach ($observations as $l)
                    <div><p class="font-medium">{{ $l['site']->nom }}</p><p class="whitespace-pre-line text-gray-700">{{ $l['bilan']->observations }}</p></div>
                @endforeach
            </div>
        @endif

        <div class="mt-16 grid grid-cols-2 gap-8 text-sm text-gray-500">
            <div class="border-t border-gray-300 pt-2">Signature du gérant</div>
            <div></div>
        </div>
    </div>
</body>
</html>
