@php
    use App\Support\Format;
    $d = $b['details'] ?? [];
    $remis = max(1, $b['tickets_remis']);
    $pc = fn ($v) => str_replace('.', ',', round($v, 1)) . ' %';
    $commissions = $b['commission_agents'] + $b['commission_superviseur'];
    $commissionsAvant = $precedent['commission_agents'] + $precedent['commission_superviseur'];
@endphp
<x-app-layout>
    <x-slot name="title">Bilan {{ $site->nom }} – {{ $mois->translatedFormat('F Y') }}</x-slot>
    <x-slot name="header">Bilan mensuel</x-slot>
    <x-slot name="actions">
        <x-secondary-button onclick="window.print()">Imprimer / PDF</x-secondary-button>
    </x-slot>

    <nav class="no-print mb-4 text-sm text-gray-500">
        <a href="{{ route('bilans.index', ['mois' => $mois->format('Y-m')]) }}" class="hover:text-primary-600">Bilans mensuels</a> / <span class="text-gray-700">{{ $site->nom }}</span>
    </nav>

    @if ($donneesModifiees)
        <div class="no-print mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <span class="flex items-center gap-2"><x-icon name="alert" class="h-5 w-5" /> Des rapports ou dépenses de ce mois ont été modifiés depuis la clôture. Les chiffres ci-dessous sont ceux du jour de la clôture.</span>
            <form method="POST" action="{{ route('bilans.cloturer', [$mois->format('Y-m'), $site]) }}">
                @csrf
                <input type="hidden" name="observations" value="{{ $b['bilan']->observations }}">
                <button class="font-semibold underline hover:no-underline">Mettre à jour le bilan</button>
            </form>
        </div>
    @endif

    <div class="print-zone space-y-6">
        {{-- En-tête du compte rendu --}}
        <div class="card p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <x-application-logo class="h-11 w-auto text-primary-600" />
                    <div>
                        <p class="text-sm text-gray-500">Compte rendu mensuel</p>
                        <h2 class="text-xl font-bold text-gray-900">{{ $site->nom }} — {{ ucfirst($mois->translatedFormat('F Y')) }}</h2>
                        <p class="text-sm text-gray-500">
                            {{ $site->localisation ?? '' }}
                            @if ($site->superviseur) · Superviseur : {{ $site->superviseur->nom_complet }} @endif
                        </p>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-2">
                    @include('bilans._navigation-mois', ['route' => 'bilans.show'])
                    @if ($b['bilan'])
                        <x-badge color="green">Clôturé le {{ $b['bilan']->cloture_le->format('d/m/Y') }}</x-badge>
                    @elseif ($moisTermine)
                        <x-badge color="amber">Non clôturé – chiffres provisoires</x-badge>
                    @else
                        <x-badge color="gray">Mois en cours – chiffres provisoires</x-badge>
                    @endif
                </div>
            </div>
        </div>

        {{-- Chiffres clés --}}
        @php
            $cartes = [
                ['Ventes', Format::gnf($b['ventes']), $b['ventes'], $precedent['ventes'], false],
                ['Commissions', Format::gnf($commissions), $commissions, $commissionsAvant, true],
                ['Dépenses', Format::gnf($b['depenses']), $b['depenses'], $precedent['depenses'], true],
                ['Bénéfice net', Format::gnf($b['benefice']), $b['benefice'], $precedent['benefice'], false],
            ];
        @endphp
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            @foreach ($cartes as [$label, $affiche, $valeur, $avant, $inverse])
                <div class="card p-5">
                    <p class="text-sm text-gray-500">{{ $label }}</p>
                    <p @class(['mt-1 text-2xl font-bold tabular-nums', 'text-red-600' => $valeur < 0, 'text-gray-900' => $valeur >= 0])>{{ $affiche }}</p>
                    @include('bilans._evolution', ['actuel' => $valeur, 'avant' => $avant, 'inverse' => $inverse])
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Activité --}}
            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">Activité</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Lots remis dans le mois</dt><dd class="font-medium tabular-nums">{{ $b['lots_remis'] }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Rapports de fin de lot</dt><dd class="font-medium tabular-nums">{{ $b['rapports'] }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Tickets remis (rapports)</dt><dd class="font-medium tabular-nums">{{ number_format($b['tickets_remis'], 0, ',', ' ') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Vendus</dt><dd class="font-medium tabular-nums text-green-700">{{ number_format($b['vendus'], 0, ',', ' ') }} <span class="text-gray-400">({{ $pc($b['vendus'] * 100 / $remis) }})</span></dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Défectueux</dt><dd class="font-medium tabular-nums text-red-600">{{ $b['defectueux'] }} <span class="text-gray-400">({{ $pc($b['defectueux'] * 100 / $remis) }})</span></dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Rendus</dt><dd class="font-medium tabular-nums">{{ $b['rendus'] }}</dd></div>
                </dl>
            </div>

            {{-- Argent --}}
            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">Résultat</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Ventes</dt><dd class="font-semibold tabular-nums">{{ Format::gnf($b['ventes']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Commissions agents</dt><dd class="tabular-nums">− {{ Format::gnf($b['commission_agents']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Commission superviseur</dt><dd class="tabular-nums">− {{ Format::gnf($b['commission_superviseur']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Dépenses du site</dt><dd class="tabular-nums">− {{ Format::gnf($b['depenses']) }}</dd></div>
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-base"><dt class="font-semibold">Bénéfice net</dt><dd @class(['font-bold tabular-nums', 'text-red-600' => $b['benefice'] < 0])>{{ Format::gnf($b['benefice']) }}</dd></div>
                </dl>
            </div>

            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">Argent des agents</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Argent versé</dt><dd class="font-medium tabular-nums">{{ Format::gnf($b['montant_verse']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Manquants</dt><dd @class(['font-semibold tabular-nums', 'text-red-600' => $b['manquants'] > 0])>{{ Format::gnf($b['manquants']) }}</dd></div>
                </dl>
            </div>
        </div>

        {{-- Par forfait --}}
        <div class="card overflow-hidden">
            <div class="px-6 py-4"><h3 class="font-semibold text-gray-900">Ventes par forfait</h3></div>
            @if (empty($d['forfaits']))
                <p class="border-t border-gray-100 px-6 py-5 text-sm text-gray-500">Aucun rapport ce mois-ci.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 border-t border-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr><th class="px-6 py-2">Forfait</th><th class="px-3 py-2 text-right">Remis</th><th class="px-3 py-2 text-right">Vendus</th><th class="px-3 py-2 text-right">Défect.</th><th class="px-3 py-2 text-right">Rendus</th><th class="px-6 py-2 text-right">Ventes</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($d['forfaits'] as $f)
                                <tr>
                                    <td class="px-6 py-2.5 font-medium">{{ $f['forfait'] }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">{{ $f['remis'] }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums text-green-700">{{ $f['vendus'] }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums {{ $f['defectueux'] ? 'text-red-600' : 'text-gray-400' }}">{{ $f['defectueux'] }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">{{ $f['rendus'] }}</td>
                                    <td class="whitespace-nowrap px-6 py-2.5 text-right font-semibold tabular-nums">{{ Format::gnf($f['ventes']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Par agent --}}
            <div class="card overflow-hidden">
                <div class="px-6 py-4"><h3 class="font-semibold text-gray-900">Agents</h3></div>
                @if (empty($d['agents']))
                    <p class="border-t border-gray-100 px-6 py-5 text-sm text-gray-500">Aucun rapport ce mois-ci.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 border-t border-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr><th class="px-3 py-2">Agent</th><th class="px-3 py-2 text-right">Vendus</th><th class="px-3 py-2 text-right">Ventes</th><th class="px-3 py-2 text-right">Comm.</th><th class="px-3 py-2 text-right">Manq.</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($d['agents'] as $a)
                                    <tr>
                                        <td class="px-3 py-2.5 font-medium">{{ $a['agent'] }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ $a['vendus'] }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ Format::gnf($a['ventes']) }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums text-gray-600">{{ Format::gnf($a['commission']) }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums {{ $a['manquants'] ? 'text-red-600' : 'text-gray-400' }}">{{ Format::gnf($a['manquants']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Dépenses --}}
            <div class="card overflow-hidden">
                <div class="px-6 py-4"><h3 class="font-semibold text-gray-900">Dépenses du mois</h3></div>
                @if (empty($d['depenses']))
                    <p class="border-t border-gray-100 px-6 py-5 text-sm text-gray-500">Aucune dépense ce mois-ci.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-100 border-t border-gray-100 text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($d['depenses'] as $dep)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-gray-500">{{ \Illuminate\Support\Carbon::parse($dep['date'])->format('d/m') }}</td>
                                    <td class="px-3 py-2.5"><span class="font-medium">{{ $dep['libelle'] }}</span><span class="block text-xs text-gray-500">{{ $dep['categorie'] }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-right font-semibold tabular-nums">{{ Format::gnf($dep['montant']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-gray-200 bg-gray-50 font-semibold">
                            <tr><td class="px-4 py-2.5" colspan="2">Total</td><td class="whitespace-nowrap px-4 py-2.5 text-right tabular-nums">{{ Format::gnf($b['depenses']) }}</td></tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        </div>

        {{-- Rapports du mois --}}
        @if (! empty($d['rapports']))
            <div class="card overflow-hidden">
                <div class="px-6 py-4"><h3 class="font-semibold text-gray-900">Rapports de fin de lot du mois</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 border-t border-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr><th class="px-6 py-2">Rapport</th><th class="px-3 py-2">Date</th><th class="px-3 py-2">Agent</th><th class="px-3 py-2 text-right">Vendus</th><th class="px-3 py-2 text-right">Défect.</th><th class="px-3 py-2 text-right">Ventes</th><th class="px-6 py-2 text-right">Écart</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($d['rapports'] as $r)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-2.5 font-mono text-xs"><a href="{{ route('rapports.show', $r['id']) }}" class="hover:text-primary-600">{{ $r['code'] }}</a> <span class="text-gray-400">· {{ $r['lot'] }}</span></td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-gray-600">{{ \Illuminate\Support\Carbon::parse($r['date'])->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2.5">{{ $r['agent'] }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">{{ $r['vendus'] }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">{{ $r['defectueux'] }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ Format::gnf($r['ventes']) }}</td>
                                    <td @class(['whitespace-nowrap px-6 py-2.5 text-right tabular-nums', 'text-red-600' => $r['ecart'] < 0, 'text-gray-400' => $r['ecart'] === 0])>{{ $r['ecart'] === 0 ? 'OK' : Format::gnf($r['ecart']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Observations & clôture --}}
        <div class="card p-6">
            <h3 class="font-semibold text-gray-900">Observations du gérant</h3>

            @if ($b['bilan'])
                {{-- Version imprimée --}}
                <p class="mt-3 hidden whitespace-pre-line text-sm text-gray-700 print:block">{{ $b['bilan']->observations ?: 'Aucune observation.' }}</p>

                <form method="POST" action="{{ route('bilans.observations', [$mois->format('Y-m'), $site]) }}" class="no-print mt-3">
                    @csrf
                    @method('PATCH')
                    <x-textarea name="observations" rows="4" class="block w-full" placeholder="Points forts, problèmes rencontrés, décisions...">{{ old('observations', $b['bilan']->observations) }}</x-textarea>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <x-primary-button>Enregistrer les observations</x-primary-button>
                        <x-confirm-delete :action="route('bilans.rouvrir', [$mois->format('Y-m'), $site])" name="rouvrir-bilan"
                                          title="Rouvrir le bilan" message="Les chiffres figés seront effacés et recalculés en direct. Les observations seront perdues.">
                            <button type="button" class="text-sm font-medium text-gray-500 hover:text-red-600">Rouvrir le bilan</button>
                        </x-confirm-delete>
                    </div>
                </form>
            @elseif ($moisTermine)
                <form method="POST" action="{{ route('bilans.cloturer', [$mois->format('Y-m'), $site]) }}" class="no-print mt-3">
                    @csrf
                    <x-textarea name="observations" rows="4" class="block w-full" placeholder="Points forts, problèmes rencontrés, décisions...">{{ old('observations') }}</x-textarea>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <x-primary-button>Clôturer le bilan de {{ $mois->translatedFormat('F') }}</x-primary-button>
                        <p class="text-xs text-gray-500">Les chiffres seront figés à la date d'aujourd'hui.</p>
                    </div>
                </form>
            @else
                <p class="mt-2 text-sm text-gray-500">Le mois n'est pas terminé : vous pourrez clôturer ce bilan à partir du 1er {{ $mois->copy()->addMonthNoOverflow()->translatedFormat('F') }}.</p>
            @endif
        </div>

        <div class="mt-12 hidden grid-cols-2 gap-8 text-sm text-gray-500 print:grid">
            <div class="border-t border-gray-300 pt-2">Le gérant</div>
            <div class="border-t border-gray-300 pt-2">Le superviseur</div>
        </div>
    </div>
</x-app-layout>
