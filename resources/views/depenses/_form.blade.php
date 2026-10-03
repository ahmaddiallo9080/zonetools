{{-- Variables : $depense, $sites --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Dépense</h2>
            <p class="mt-1 text-sm text-gray-500">Les champs marqués d'un <span class="text-red-500">*</span> sont obligatoires.</p>

            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="site_id">Site <span class="text-red-500">*</span></x-input-label>
                    @php $siteChoisi = (string) old('site_id', $depense->exists && ! $depense->site_id ? 'general' : $depense->site_id); @endphp
                    <x-select id="site_id" name="site_id" class="mt-1 block w-full" required>
                        <option value="">— Choisir un site —</option>
                        <option value="general" @selected($siteChoisi === 'general')>Général (tous les sites)</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" @selected($siteChoisi === (string) $site->id)>{{ $site->nom }}</option>
                        @endforeach
                    </x-select>
                    <p class="mt-1 text-xs text-gray-500">« Général » pour une dépense commune à tout le réseau.</p>
                    <x-input-error :messages="$errors->get('site_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="categorie">Catégorie <span class="text-red-500">*</span></x-input-label>
                    <x-select id="categorie" name="categorie" class="mt-1 block w-full" required>
                        <option value="">— Choisir —</option>
                        @foreach (\App\Models\Depense::CATEGORIES as $cle => $label)
                            <option value="{{ $cle }}" @selected(old('categorie', $depense->categorie) === $cle)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('categorie')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="libelle">Libellé <span class="text-red-500">*</span></x-input-label>
                    <x-text-input id="libelle" name="libelle" type="text" class="mt-1 block w-full" :value="old('libelle', $depense->libelle)" required placeholder="Ex : Recharge abonnement fibre octobre" />
                    <x-input-error :messages="$errors->get('libelle')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="montant">Montant <span class="text-red-500">*</span></x-input-label>
                    <div class="relative mt-1">
                        <x-text-input id="montant" name="montant" type="text" inputmode="numeric" class="block w-full pe-14" :value="old('montant', $depense->montant)" required placeholder="0" />
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pe-3 text-sm text-gray-500">GNF</span>
                    </div>
                    <x-input-error :messages="$errors->get('montant')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="date_depense">Date <span class="text-red-500">*</span></x-input-label>
                    <x-text-input id="date_depense" name="date_depense" type="date" class="mt-1 block w-full" required
                                  :value="old('date_depense', $depense->date_depense?->format('Y-m-d'))" :max="now()->format('Y-m-d')" />
                    <x-input-error :messages="$errors->get('date_depense')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="mode">Mode de paiement <span class="text-red-500">*</span></x-input-label>
                    <x-select id="mode" name="mode" class="mt-1 block w-full">
                        @foreach (\App\Models\Paiement::MODES as $cle => $label)
                            <option value="{{ $cle }}" @selected(old('mode', $depense->mode) === $cle)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('mode')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="fournisseur" value="Fournisseur / bénéficiaire" />
                    <x-text-input id="fournisseur" name="fournisseur" type="text" class="mt-1 block w-full" :value="old('fournisseur', $depense->fournisseur)" placeholder="Ex : Orange Guinée, EDG..." />
                    <x-input-error :messages="$errors->get('fournisseur')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="reference" value="Référence (n° de facture, de reçu ou de transaction)" />
                    <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference', $depense->reference)" />
                    <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="notes" value="Notes" />
                    <x-textarea id="notes" name="notes" class="mt-1 block w-full">{{ old('notes', $depense->notes) }}</x-textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        {{-- Justificatif --}}
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Justificatif</h2>
            <p class="mt-1 text-sm text-gray-500">Photo de la facture ou du reçu (JPG, PNG, PDF – 5 Mo max).</p>

            @if ($depense->justificatif)
                <div class="mt-4 rounded-lg border border-gray-200 p-3 text-sm">
                    <a href="{{ route('depenses.justificatif', $depense) }}" target="_blank" class="font-medium text-primary-600 hover:text-primary-700">Voir le justificatif actuel</a>
                    <label class="mt-2 flex items-center gap-2 text-gray-600">
                        <input type="checkbox" name="supprimer_justificatif" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500"> Supprimer
                    </label>
                </div>
            @endif

            <input type="file" name="fichier" accept=".jpg,.jpeg,.png,.webp,.pdf"
                   class="mt-4 block w-full text-sm text-gray-600 file:me-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100">
            <x-input-error :messages="$errors->get('fichier')" class="mt-2" />
        </div>

        <div class="card p-6">
            <x-input-label for="code" value="Numéro de la dépense" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full uppercase" :value="old('code', $depense->code)" :placeholder="$codeSuggere ?? ''" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ $depense->exists ? route('depenses.show', $depense) : route('depenses.index') }}">
                <x-secondary-button>Annuler</x-secondary-button>
            </a>
            @unless ($depense->exists)
                <x-secondary-button type="submit" name="encore" value="1">Enregistrer et ajouter une autre</x-secondary-button>
            @endunless
            <x-primary-button>Enregistrer</x-primary-button>
        </div>
    </div>
</div>
