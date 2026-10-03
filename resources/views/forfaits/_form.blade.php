@php
    /** @var \App\Models\Forfait $forfait */
    $lignesInitiales = old('prix_sites', $prixSites);
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        {{-- Caractéristiques --}}
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Caractéristiques du forfait</h2>
            <p class="mt-1 text-sm text-gray-500">Les champs marqués d'un <span class="text-red-500">*</span> sont obligatoires.</p>

            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="nom">Nom du forfait <span class="text-red-500">*</span></x-input-label>
                    <x-text-input id="nom" name="nom" type="text" class="mt-1 block w-full" :value="old('nom', $forfait->nom)" required autofocus placeholder="Ex : Pass 1 heure" />
                    <x-input-error :messages="$errors->get('nom')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="code" value="Code" />
                    <x-text-input id="code" name="code" type="text" class="mt-1 block w-full uppercase" :value="old('code', $forfait->code)" :placeholder="$codeSuggere ?? ''" />
                    <p class="mt-1 text-xs text-gray-500">Laissez vide pour générer automatiquement.</p>
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="duree_valeur">Durée de validité <span class="text-red-500">*</span></x-input-label>
                    <div class="mt-1 flex gap-2">
                        <x-text-input id="duree_valeur" name="duree_valeur" type="number" min="1" class="block w-24" :value="old('duree_valeur', $forfait->duree_valeur)" required />
                        <x-select name="duree_unite" class="block flex-1">
                            @foreach (\App\Models\Forfait::UNITES as $valeur => $unite)
                                <option value="{{ $valeur }}" @selected(old('duree_unite', $forfait->duree_unite) === $valeur)>{{ ucfirst($unite['pluriel']) }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <x-input-error :messages="$errors->get('duree_valeur')" class="mt-2" />
                    <x-input-error :messages="$errors->get('duree_unite')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="nb_appareils">Nombre d'appareils <span class="text-red-500">*</span></x-input-label>
                    <x-text-input id="nb_appareils" name="nb_appareils" type="number" min="1" max="50" class="mt-1 block w-full" :value="old('nb_appareils', $forfait->nb_appareils)" required />
                    <p class="mt-1 text-xs text-gray-500">Appareils pouvant se connecter en même temps avec un ticket.</p>
                    <x-input-error :messages="$errors->get('nb_appareils')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="prix">Prix de base <span class="text-red-500">*</span></x-input-label>
                    <div class="relative mt-1">
                        <x-text-input id="prix" name="prix" type="text" inputmode="numeric" class="block w-full pe-14" :value="old('prix', $forfait->prix)" required placeholder="Ex : 1000" />
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pe-3 text-sm text-gray-500">GNF</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Appliqué sur tous les sites sans prix particulier.</p>
                    <x-input-error :messages="$errors->get('prix')" class="mt-2" />
                </div>

                <div>
                    <x-input-label value="Couleur de repère" />
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (\App\Models\Forfait::COULEURS as $valeur => $label)
                            <label class="cursor-pointer rounded-full p-0.5 ring-2 ring-transparent has-[:checked]:ring-primary-500">
                                <input type="radio" name="couleur" value="{{ $valeur }}" class="sr-only" @checked(old('couleur', $forfait->couleur) === $valeur)>
                                <x-badge :color="$valeur">{{ $label }}</x-badge>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('couleur')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="description" value="Description" />
                    <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $forfait->description)" placeholder="Ex : Idéal pour une consultation rapide" />
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
            </div>
        </div>

        {{-- Prix particuliers par site --}}
        <div class="card p-6" x-data="{ lignes: @js(array_values($lignesInitiales)) }">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Prix particuliers par site</h2>
                    <p class="mt-1 text-sm text-gray-500">Ajoutez un site seulement si son prix diffère du prix de base.</p>
                </div>
                <x-secondary-button @click="lignes.push({ site_id: '', prix: '' })" :disabled="$sites->isEmpty()">+ Ajouter un site</x-secondary-button>
            </div>

            @if ($errors->has('prix_sites.*'))
                <ul class="mt-3 space-y-1 text-sm text-red-600">
                    @foreach (collect($errors->get('prix_sites.*'))->flatten()->unique() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @endif

            <template x-if="lignes.length === 0">
                <p class="mt-4 rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500">
                    Aucun prix particulier : le prix de base s'applique sur tous les sites.
                </p>
            </template>

            <div class="mt-4 space-y-3">
                <template x-for="(ligne, index) in lignes" :key="index">
                    <div class="flex items-center gap-3">
                        <x-select x-bind:name="`prix_sites[${index}][site_id]`" x-model="ligne.site_id" class="block flex-1">
                            <option value="">— Choisir un site —</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->nom }}</option>
                            @endforeach
                        </x-select>
                        <div class="relative w-40">
                            <x-text-input type="text" inputmode="numeric" x-bind:name="`prix_sites[${index}][prix]`" x-model="ligne.prix" class="block w-full pe-12" placeholder="Prix" />
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pe-3 text-xs text-gray-500">GNF</span>
                        </div>
                        <button type="button" @click="lignes.splice(index, 1)" title="Retirer" class="rounded-lg p-2 text-red-600 hover:bg-red-50">
                            <x-icon name="trash" class="h-5 w-5" />
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Statut</h2>
            <div class="mt-4 grid grid-cols-2 gap-3">
                @foreach (\App\Models\Forfait::STATUTS as $valeur => $statut)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-3 py-2.5 hover:bg-gray-50 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                        <input type="radio" name="statut" value="{{ $valeur }}" class="text-primary-600 focus:ring-primary-500" @checked(old('statut', $forfait->statut) === $valeur)>
                        <x-badge :color="$statut['color']">{{ $statut['label'] }}</x-badge>
                    </label>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-gray-500">Un forfait inactif ne peut plus être choisi pour un nouveau lot.</p>
            <x-input-error :messages="$errors->get('statut')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ $forfait->exists ? route('forfaits.show', $forfait) : route('forfaits.index') }}">
                <x-secondary-button>Annuler</x-secondary-button>
            </a>
            <x-primary-button>{{ $forfait->exists ? 'Enregistrer' : 'Créer le forfait' }}</x-primary-button>
        </div>
    </div>
</div>
