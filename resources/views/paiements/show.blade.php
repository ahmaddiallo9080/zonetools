@php use App\Support\Format; $b = $paiement->beneficiaire; @endphp
<x-app-layout>
    <x-slot name="title">Paiement {{ $paiement->code }}</x-slot>
    <x-slot name="header">Paiement {{ $paiement->code }}</x-slot>
    <x-slot name="actions">
        <x-secondary-button onclick="window.print()">Imprimer le reçu</x-secondary-button>
        <a href="{{ route('paiements.edit', $paiement) }}"><x-primary-button type="button">Modifier</x-primary-button></a>
    </x-slot>

    <nav class="no-print mb-4 text-sm text-gray-500">
        <a href="{{ route('paiements.index') }}" class="hover:text-primary-600">Paiements</a> / <span class="text-gray-700">{{ $paiement->code }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Reçu --}}
        <div class="card p-8 lg:col-span-2 print-zone">
            <div class="flex items-start justify-between border-b border-gray-200 pb-6">
                <div class="flex items-center gap-3">
                    <x-application-logo class="h-11 w-auto text-primary-600" />
                    <div>
                        <x-logo-texte class="h-5 w-auto text-gray-900" />
                        <p class="mt-1 text-sm text-gray-500">Reçu de paiement de commission</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-mono text-lg font-semibold">{{ $paiement->code }}</p>
                    <p class="text-sm text-gray-500">{{ $paiement->date_paiement->translatedFormat('d F Y') }}</p>
                </div>
            </div>

            <dl class="mt-6 grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                <div><dt class="text-gray-500">Bénéficiaire</dt><dd class="mt-0.5 text-base font-semibold">{{ $b?->nom_complet }}</dd></div>
                <div><dt class="text-gray-500">Fonction</dt><dd class="mt-0.5 font-medium">{{ $paiement->type_label }} · <span class="font-mono text-xs">{{ $b?->code }}</span></dd></div>
                <div><dt class="text-gray-500">Mode de paiement</dt><dd class="mt-0.5 font-medium">{{ $paiement->mode_label }}</dd></div>
                <div><dt class="text-gray-500">Référence</dt><dd class="mt-0.5 font-mono">{{ $paiement->reference ?? '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-gray-500">Période couverte</dt><dd class="mt-0.5 font-medium">{{ $paiement->periode_label ?? 'Non précisée' }}</dd></div>
            </dl>

            <div class="mt-8 flex items-center justify-between rounded-xl bg-primary-600 px-6 py-5 text-white">
                <span class="text-sm uppercase tracking-wide text-primary-100">Montant payé</span>
                <span class="text-3xl font-bold">{{ $paiement->montant_affiche }}</span>
            </div>

            @if ($paiement->notes)
                <p class="mt-6 whitespace-pre-line text-sm text-gray-700">{{ $paiement->notes }}</p>
            @endif

            <div class="mt-12 grid grid-cols-2 gap-8 text-sm text-gray-500">
                <div class="border-t border-gray-300 pt-2">Signature du gérant</div>
                <div class="border-t border-gray-300 pt-2">Signature du bénéficiaire</div>
            </div>
        </div>

        <div class="no-print space-y-6">
            <div class="card p-6">
                <h3 class="font-semibold text-gray-900">Situation de {{ $b?->prenom }}</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Commissions dues</dt><dd class="font-medium">{{ Format::gnf($solde->dues) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Total payé</dt><dd class="font-medium text-green-700">{{ Format::gnf($solde->payees) }}</dd></div>
                    <div class="flex justify-between border-t border-gray-100 pt-2"><dt class="font-medium">Reste à payer</dt><dd class="font-bold">{{ Format::gnf($solde->solde) }}</dd></div>
                </dl>
                <a href="{{ route($paiement->beneficiaire_type.'s.show', $b) }}" class="mt-4 inline-block text-sm font-medium text-primary-600 hover:text-primary-700">Voir la fiche</a>
            </div>

            <div class="card space-y-1 p-6 text-sm text-gray-500">
                <p>Saisi le {{ $paiement->created_at->format('d/m/Y à H:i') }}</p>
                <p>Modifié le {{ $paiement->updated_at->format('d/m/Y à H:i') }}</p>
            </div>

            <div class="card border-red-200 p-6">
                <h3 class="font-semibold text-red-700">Zone de danger</h3>
                <p class="mt-1 text-sm text-gray-500">Le montant sera de nouveau compté comme dû.</p>
                <x-confirm-delete :action="route('paiements.destroy', $paiement)" name="supprimer-paiement"
                                  :message="'Le paiement '.$paiement->code.' de '.$paiement->montant_affiche.' sera supprimé.'">
                    <x-danger-button type="button" class="mt-4">Supprimer le paiement</x-danger-button>
                </x-confirm-delete>
            </div>
        </div>
    </div>
</x-app-layout>
