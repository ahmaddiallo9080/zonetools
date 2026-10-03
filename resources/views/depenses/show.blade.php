<x-app-layout>
    <x-slot name="title">Dépense {{ $depense->code }}</x-slot>
    <x-slot name="header">Dépense {{ $depense->code }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('depenses.edit', $depense) }}"><x-primary-button type="button">Modifier</x-primary-button></a>
    </x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('depenses.index') }}" class="hover:text-primary-600">Dépenses</a> / <span class="text-gray-700">{{ $depense->code }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-600 text-white"><x-icon name="receipt" class="h-6 w-6" /></span>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $depense->libelle }}</h2>
                            <p class="text-sm text-gray-500"><span class="font-mono">{{ $depense->code }}</span> · {{ $depense->date_depense->translatedFormat('d F Y') }}</p>
                        </div>
                    </div>
                    <x-badge color="gray">{{ $depense->categorie_label }}</x-badge>
                </div>

                <div class="mt-6 flex items-center justify-between rounded-xl bg-gray-50 px-6 py-4">
                    <span class="text-sm text-gray-500">Montant</span>
                    <span class="text-2xl font-bold tabular-nums text-gray-900">{{ $depense->montant_affiche }}</span>
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-gray-500">Site</dt>
                        <dd class="mt-0.5 font-medium">
                            @if ($depense->site)
                                <a href="{{ route('sites.show', $depense->site) }}" class="hover:text-primary-600">{{ $depense->site->nom }}</a>
                            @else
                                Général (tous les sites)
                            @endif
                        </dd>
                    </div>
                    <div><dt class="text-gray-500">Mode de paiement</dt><dd class="mt-0.5 font-medium">{{ $depense->mode_label }}</dd></div>
                    <div><dt class="text-gray-500">Fournisseur / bénéficiaire</dt><dd class="mt-0.5 font-medium">{{ $depense->fournisseur ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Référence</dt><dd class="mt-0.5 font-mono">{{ $depense->reference ?? '—' }}</dd></div>
                </dl>

                @if ($depense->notes)
                    <div class="mt-6 whitespace-pre-line rounded-lg bg-gray-50 p-4 text-sm text-gray-700">{{ $depense->notes }}</div>
                @endif
            </div>

            @if ($depense->justificatif)
                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">Justificatif</h3>
                        <a href="{{ route('depenses.justificatif', $depense) }}" target="_blank" class="text-sm font-medium text-primary-600 hover:text-primary-700">Ouvrir</a>
                    </div>
                    @if ($depense->justificatifEstImage())
                        <img src="{{ route('depenses.justificatif', $depense) }}" alt="Justificatif {{ $depense->code }}" class="mt-4 max-h-[480px] rounded-lg border border-gray-200">
                    @else
                        <p class="mt-2 text-sm text-gray-500">Document PDF joint.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="card space-y-1 p-6 text-sm text-gray-500">
                <p>Saisie le {{ $depense->created_at->format('d/m/Y à H:i') }}</p>
                <p>Modifiée le {{ $depense->updated_at->format('d/m/Y à H:i') }}</p>
            </div>

            <div class="card border-red-200 p-6">
                <h3 class="font-semibold text-red-700">Zone de danger</h3>
                <p class="mt-1 text-sm text-gray-500">La dépense sera retirée des listes et des totaux.</p>
                <x-confirm-delete :action="route('depenses.destroy', $depense)" name="supprimer-depense"
                                  :message="'La dépense « '.$depense->libelle.' » sera supprimée.'">
                    <x-danger-button type="button" class="mt-4">Supprimer la dépense</x-danger-button>
                </x-confirm-delete>
            </div>
        </div>
    </div>
</x-app-layout>
