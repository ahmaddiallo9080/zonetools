{{-- Variables : $paiement, $beneficiaires, $beneficiaireChoisi, $soldes --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3"
     x-data="{
        beneficiaire: @js(old('beneficiaire', $beneficiaireChoisi ?? '')),
        montant: @js((string) old('montant', $paiement->montant ?? '')),
        soldes: @js($soldes),
        solde() { return this.soldes[this.beneficiaire] ?? null; },
        n(v) { return parseInt(String(v ?? '').replace(/[^\d]/g, '')) || 0; },
        gnf(v) { return new Intl.NumberFormat('fr-FR').format(v || 0).replace(/ /g, ' ') + ' GNF'; },
     }">
    <div class="space-y-6 lg:col-span-2">
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Paiement de commission</h2>
            <p class="mt-1 text-sm text-gray-500">Les champs marqués d'un <span class="text-red-500">*</span> sont obligatoires.</p>

            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="beneficiaire">Bénéficiaire <span class="text-red-500">*</span></x-input-label>
                    <select id="beneficiaire" name="beneficiaire" x-model="beneficiaire" required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">— Choisir un superviseur ou un agent —</option>
                        @foreach ($beneficiaires as $groupe => $options)
                            <optgroup label="{{ $groupe }}">
                                @foreach ($options as $cle => $nom)
                                    <option value="{{ $cle }}" @selected(old('beneficiaire', $beneficiaireChoisi ?? '') === $cle)>{{ $nom }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('beneficiaire')" class="mt-2" />
                    <x-input-error :messages="$errors->get('beneficiaire_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="montant">Montant <span class="text-red-500">*</span></x-input-label>
                    <div class="relative mt-1">
                        <input id="montant" name="montant" type="text" inputmode="numeric" x-model="montant" required placeholder="0"
                               class="block w-full rounded-lg border-gray-300 pe-14 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pe-3 text-sm text-gray-500">GNF</span>
                    </div>
                    <button type="button" x-show="solde() > 0" @click="montant = String(solde())" class="mt-1 text-xs font-medium text-primary-600 hover:text-primary-700">
                        Payer tout le solde (<span x-text="gnf(solde())"></span>)
                    </button>
                    <x-input-error :messages="$errors->get('montant')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="date_paiement">Date du paiement <span class="text-red-500">*</span></x-input-label>
                    <x-text-input id="date_paiement" name="date_paiement" type="date" class="mt-1 block w-full" required
                                  :value="old('date_paiement', $paiement->date_paiement?->format('Y-m-d'))" :max="now()->format('Y-m-d')" />
                    <x-input-error :messages="$errors->get('date_paiement')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="mode">Mode de paiement <span class="text-red-500">*</span></x-input-label>
                    <x-select id="mode" name="mode" class="mt-1 block w-full">
                        @foreach (\App\Models\Paiement::MODES as $cle => $label)
                            <option value="{{ $cle }}" @selected(old('mode', $paiement->mode) === $cle)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('mode')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="reference" value="Référence / n° de transaction" />
                    <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference', $paiement->reference)" placeholder="Ex : MP261003.1234.A56789" />
                    <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="periode_du" value="Période couverte : du" />
                    <x-text-input id="periode_du" name="periode_du" type="date" class="mt-1 block w-full" :value="old('periode_du', $paiement->periode_du?->format('Y-m-d'))" />
                    <x-input-error :messages="$errors->get('periode_du')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="periode_au" value="au" />
                    <x-text-input id="periode_au" name="periode_au" type="date" class="mt-1 block w-full" :value="old('periode_au', $paiement->periode_au?->format('Y-m-d'))" />
                    <x-input-error :messages="$errors->get('periode_au')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="notes" value="Notes" />
                    <x-textarea id="notes" name="notes" class="mt-1 block w-full">{{ old('notes', $paiement->notes) }}</x-textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-6" x-show="beneficiaire">
            <h2 class="text-base font-semibold text-gray-900">Solde du bénéficiaire</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Reste à payer</dt><dd class="font-semibold" x-text="gnf(solde())"></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Ce paiement</dt><dd class="font-semibold text-green-700" x-text="'− ' + gnf(n(montant))"></dd></div>
                <div class="flex justify-between border-t border-gray-100 pt-2"><dt class="font-medium">Après paiement</dt><dd class="font-bold" :class="(solde() - n(montant)) < 0 ? 'text-amber-700' : 'text-gray-900'" x-text="gnf(solde() - n(montant))"></dd></div>
            </dl>
            <p x-show="(solde() - n(montant)) < 0" class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                Le montant dépasse ce qui est dû : la différence sera comptée comme une avance.
            </p>
        </div>

        <div class="card p-6">
            <x-input-label for="code" value="Numéro du paiement" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full uppercase" :value="old('code', $paiement->code)" :placeholder="$codeSuggere ?? ''" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ $paiement->exists ? route('paiements.show', $paiement) : route('versements.index') }}">
                <x-secondary-button>Annuler</x-secondary-button>
            </a>
            <x-primary-button>{{ $paiement->exists ? 'Enregistrer' : 'Enregistrer le paiement' }}</x-primary-button>
        </div>
    </div>
</div>
