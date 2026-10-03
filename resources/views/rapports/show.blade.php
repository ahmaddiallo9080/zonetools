@php use App\Support\Format; $lot = $rapport->lot; @endphp
<x-app-layout>
    <x-slot name="title">Rapport {{ $rapport->code }}</x-slot>
    <x-slot name="header">Rapport {{ $rapport->code }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('rapports.edit', $rapport) }}"><x-primary-button type="button">Modifier</x-primary-button></a>
    </x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('rapports.index') }}" class="hover:text-primary-600">Rapports</a> / <span class="text-gray-700">{{ $rapport->code }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- En-tête --}}
            <div class="card p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-600 text-white"><x-icon name="clipboard" class="h-6 w-6" /></span>
                        <div>
                            <h2 class="font-mono text-lg font-semibold text-gray-900">{{ $rapport->code }}</h2>
                            <p class="text-sm text-gray-500">Rapport du {{ $rapport->date_rapport->translatedFormat('d F Y') }}</p>
                        </div>
                    </div>
                    <x-badge :color="$rapport->ecart_color">{{ $rapport->ecart_label }}</x-badge>
                </div>

                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div class="rounded-lg bg-gray-50 p-3"><dt class="text-gray-500">Lot</dt><dd class="mt-1 font-mono font-semibold"><a href="{{ route('lots.show', $lot) }}" class="hover:text-primary-600">{{ $lot->code }}</a></dd></div>
                    <div class="rounded-lg bg-gray-50 p-3"><dt class="text-gray-500">Site</dt><dd class="mt-1 font-semibold">{{ $lot->site?->nom }}</dd></div>
                    <div class="rounded-lg bg-gray-50 p-3"><dt class="text-gray-500">Agent</dt><dd class="mt-1 font-semibold">{{ $lot->agent?->nom_complet }}</dd></div>
                    <div class="rounded-lg bg-gray-50 p-3"><dt class="text-gray-500">Superviseur</dt><dd class="mt-1 font-semibold">{{ $lot->superviseur?->nom_complet ?? '—' }}</dd></div>
                </dl>
            </div>

            {{-- Indicateurs tickets --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="card p-4"><p class="text-sm text-gray-500">Remis</p><p class="mt-1 text-2xl font-bold">{{ $rapport->quantite_remise }}</p></div>
                <div class="card p-4"><p class="text-sm text-gray-500">Vendus</p><p class="mt-1 text-2xl font-bold text-green-700">{{ $rapport->quantite_vendue }}</p></div>
                <div class="card p-4"><p class="text-sm text-gray-500">Défectueux</p><p class="mt-1 text-2xl font-bold text-red-600">{{ $rapport->quantite_defectueuse }}</p><p class="text-xs text-gray-500">{{ str_replace('.', ',', $rapport->taux_defectueux) }} %</p></div>
                <div class="card p-4"><p class="text-sm text-gray-500">Rendus</p><p class="mt-1 text-2xl font-bold text-gray-700">{{ $rapport->quantite_rendue }}</p></div>
            </div>

            {{-- Détail --}}
            <div class="card overflow-hidden">
                <div class="px-6 py-4"><h3 class="font-semibold text-gray-900">Détail par forfait</h3></div>
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 border-t border-gray-100 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-3 py-3">Forfait</th>
                            <th class="px-3 py-3 text-right">Remis</th>
                            <th class="px-3 py-3 text-right">Vendus</th>
                            <th class="px-3 py-3 text-right">Défect.</th>
                            <th class="px-3 py-3 text-right">Rendus</th>
                            <th class="px-3 py-3 text-right">Prix</th>
                            <th class="whitespace-nowrap px-3 py-3 text-right">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($rapport->lignes as $ligne)
                            <tr>
                                <td class="px-3 py-3"><x-badge :color="$ligne->forfait->couleur">{{ $ligne->forfait->nom }}</x-badge></td>
                                <td class="px-3 py-3 text-right">{{ $ligne->quantite_remise }}</td>
                                <td class="px-3 py-3 text-right font-medium text-green-700">{{ $ligne->vendus }}</td>
                                <td class="px-3 py-3 text-right {{ $ligne->defectueux ? 'font-medium text-red-600' : 'text-gray-400' }}">{{ $ligne->defectueux }}</td>
                                <td class="px-3 py-3 text-right">{{ $ligne->rendus }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right text-gray-600">{{ Format::gnf($ligne->prix_unitaire) }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right font-semibold">{{ Format::gnf($ligne->montant_vendu) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-gray-200 bg-gray-50 font-semibold">
                        <tr>
                            <td class="px-3 py-3">Total</td>
                            <td class="px-3 py-3 text-right">{{ $rapport->quantite_remise }}</td>
                            <td class="px-3 py-3 text-right text-green-700">{{ $rapport->quantite_vendue }}</td>
                            <td class="px-3 py-3 text-right text-red-600">{{ $rapport->quantite_defectueuse }}</td>
                            <td class="px-3 py-3 text-right">{{ $rapport->quantite_rendue }}</td>
                            <td></td>
                            <td class="whitespace-nowrap px-3 py-3 text-right text-base text-primary-700">{{ Format::gnf($rapport->montant_vendu) }}</td>
                        </tr>
                    </tfoot>
                </table>
                </div>
            </div>

            @if ($rapport->observations)
                <div class="card p-6">
                    <h3 class="font-semibold text-gray-900">Observations</h3>
                    <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $rapport->observations }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="card bg-primary-600 p-6 text-white">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-primary-100">Répartition</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-primary-100">Montant vendu</dt><dd class="font-semibold">{{ Format::gnf($rapport->montant_vendu) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-primary-100">Agent ({{ Format::pourcent($lot->taux_agent) }})</dt><dd>{{ Format::gnf($rapport->commission_agent) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-primary-100">Superviseur ({{ Format::pourcent($lot->taux_superviseur) }})</dt><dd>{{ Format::gnf($rapport->commission_superviseur) }}</dd></div>
                    <div class="flex justify-between border-t border-white/20 pt-2 text-base"><dt class="font-semibold">Net gérant</dt><dd class="font-bold">{{ Format::gnf($rapport->net_gerant) }}</dd></div>
                </dl>
            </div>

            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">Argent versé</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Montant attendu</dt><dd class="font-semibold">{{ Format::gnf($rapport->montant_attendu) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Montant versé</dt><dd class="font-semibold">{{ Format::gnf($rapport->montant_verse) }}</dd></div>
                </dl>
                <p class="mt-1 text-xs text-gray-500">{{ $rapport->commission_agent_deduite ? "L'agent a gardé sa commission." : "L'agent a versé la totalité des ventes." }}</p>
                <div @class(['mt-4 rounded-lg px-3 py-2 text-sm font-semibold',
                    'bg-red-50 text-red-700' => $rapport->ecart < 0,
                    'bg-amber-50 text-amber-800' => $rapport->ecart > 0,
                    'bg-green-50 text-green-700' => $rapport->ecart === 0])>
                    {{ $rapport->ecart_label }}
                </div>
            </div>

            <div class="card space-y-1 p-6 text-sm text-gray-500">
                <p>Saisi le {{ $rapport->created_at->format('d/m/Y à H:i') }}</p>
                <p>Modifié le {{ $rapport->updated_at->format('d/m/Y à H:i') }}</p>
            </div>

            <div class="card border-red-200 p-6">
                <h3 class="font-semibold text-red-700">Zone de danger</h3>
                <p class="mt-1 text-sm text-gray-500">Supprimer le rapport remet le lot {{ $lot->code }} « en cours ».</p>
                <x-confirm-delete :action="route('rapports.destroy', $rapport)" name="supprimer-rapport"
                                  :message="'Le rapport '.$rapport->code.' sera supprimé.'">
                    <x-danger-button type="button" class="mt-4">Supprimer le rapport</x-danger-button>
                </x-confirm-delete>
            </div>
        </div>
    </div>
</x-app-layout>
