<x-app-layout>
    <x-slot name="title">{{ $forfait->nom }}</x-slot>
    <x-slot name="header">{{ $forfait->nom }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('forfaits.edit', $forfait) }}"><x-primary-button type="button">Modifier</x-primary-button></a>
    </x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('forfaits.index') }}" class="hover:text-primary-600">Forfaits</a> / <span class="text-gray-700">{{ $forfait->nom }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Fiche --}}
            <div class="card p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-600 text-white">
                            <x-icon name="tag" class="h-6 w-6" />
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $forfait->nom }}</h2>
                            <p class="font-mono text-xs text-gray-500">{{ $forfait->code }}</p>
                        </div>
                    </div>
                    <x-badge :color="$forfait->statut_color">{{ $forfait->statut_label }}</x-badge>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Durée</p>
                        <p class="mt-1 text-lg font-semibold">{{ $forfait->duree_label }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Appareils</p>
                        <p class="mt-1 text-lg font-semibold">{{ $forfait->appareils_label }}</p>
                    </div>
                    <div class="rounded-lg bg-primary-50 p-4">
                        <p class="text-sm text-primary-700">Prix de base</p>
                        <p class="mt-1 text-lg font-semibold text-primary-700">{{ $forfait->prix_affiche }}</p>
                    </div>
                </div>

                @if ($forfait->description)
                    <p class="mt-6 text-sm text-gray-700">{{ $forfait->description }}</p>
                @endif
            </div>

            {{-- Prix par site --}}
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4">
                    <h3 class="font-semibold text-gray-900">Prix appliqué sur chaque site</h3>
                    <a href="{{ route('forfaits.edit', $forfait) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">Gérer les prix</a>
                </div>
                @if ($sites->isEmpty())
                    <p class="border-t border-gray-100 px-6 py-6 text-sm text-gray-500">Aucun site enregistré.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-100 border-t border-gray-100 text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($sites as $ligne)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3">
                                        <a href="{{ route('sites.show', $ligne['site']) }}" class="font-medium text-gray-900 hover:text-primary-600">{{ $ligne['site']->nom }}</a>
                                    </td>
                                    <td class="px-6 py-3">
                                        @if ($ligne['particulier'])
                                            <x-badge color="amber">Prix particulier</x-badge>
                                        @else
                                            <span class="text-xs text-gray-400">Prix de base</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right font-semibold {{ $ligne['particulier'] ? 'text-amber-700' : 'text-gray-900' }}">
                                        {{ \App\Support\Format::gnf($ligne['prix']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">En circulation</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Lots en cours</dt><dd class="font-semibold">{{ $statsLots->lots }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Tickets</dt><dd class="font-semibold">{{ number_format($statsLots->tickets, 0, ',', ' ') }}</dd></div>
                </dl>
            </div>

            <div class="card space-y-1 p-6 text-sm text-gray-500">
                <p>Créé le {{ $forfait->created_at->format('d/m/Y à H:i') }}</p>
                <p>Modifié le {{ $forfait->updated_at->format('d/m/Y à H:i') }}</p>
            </div>

            <div class="card border-red-200 p-6">
                <h3 class="font-semibold text-red-700">Zone de danger</h3>
                <p class="mt-1 text-sm text-gray-500">Pour ne plus l'utiliser sans perdre l'historique, passez-le plutôt en « Inactif ».</p>
                <x-confirm-delete :action="route('forfaits.destroy', $forfait)" name="supprimer-forfait"
                                  :message="'Le forfait « '.$forfait->nom.' » sera supprimé.'">
                    <x-danger-button type="button" class="mt-4">Supprimer le forfait</x-danger-button>
                </x-confirm-delete>
            </div>
        </div>
    </div>
</x-app-layout>
