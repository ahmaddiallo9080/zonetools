@php /** @var \App\Models\Site $site */ @endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Informations principales --}}
    <div class="card p-6 lg:col-span-2">
        <h2 class="text-base font-semibold text-gray-900">Informations du site</h2>
        <p class="mt-1 text-sm text-gray-500">Les champs marqués d'un <span class="text-red-500">*</span> sont obligatoires.</p>

        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="nom">Nom du site <span class="text-red-500">*</span></x-input-label>
                <x-text-input id="nom" name="nom" type="text" class="mt-1 block w-full" :value="old('nom', $site->nom)" required autofocus placeholder="Ex : Hotspot Kipé Centre" />
                <x-input-error :messages="$errors->get('nom')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="code" value="Code" />
                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full uppercase" :value="old('code', $site->code)" :placeholder="$codeSuggere ?? ''" />
                <p class="mt-1 text-xs text-gray-500">Laissez vide pour générer automatiquement.</p>
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="telephone" value="Téléphone du site" />
                <x-text-input id="telephone" name="telephone" type="tel" class="mt-1 block w-full" :value="old('telephone', $site->telephone)" placeholder="+224 6xx xx xx xx" />
                <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="ville" value="Ville" />
                <x-text-input id="ville" name="ville" type="text" class="mt-1 block w-full" :value="old('ville', $site->ville)" />
                <x-input-error :messages="$errors->get('ville')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="quartier" value="Quartier" />
                <x-text-input id="quartier" name="quartier" type="text" class="mt-1 block w-full" :value="old('quartier', $site->quartier)" />
                <x-input-error :messages="$errors->get('quartier')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="adresse" value="Adresse / repère" />
                <x-text-input id="adresse" name="adresse" type="text" class="mt-1 block w-full" :value="old('adresse', $site->adresse)" placeholder="Ex : En face de la pharmacie..." />
                <x-input-error :messages="$errors->get('adresse')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="notes" value="Notes" />
                <x-textarea id="notes" name="notes" class="mt-1 block w-full" rows="4">{{ old('notes', $site->notes) }}</x-textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
        </div>
    </div>

    <div class="space-y-6">
        {{-- Superviseur --}}
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Superviseur</h2>
            <x-select id="superviseur_id" name="superviseur_id" class="mt-4 block w-full">
                <option value="">— Aucun superviseur —</option>
                @foreach ($superviseurs as $sup)
                    <option value="{{ $sup->id }}" @selected((string) old('superviseur_id', $site->superviseur_id) === (string) $sup->id)>
                        {{ $sup->nom_complet }}{{ $sup->statut === 'inactif' ? ' (inactif)' : '' }}
                    </option>
                @endforeach
            </x-select>
            @if ($superviseurs->isEmpty())
                <p class="mt-2 text-xs text-gray-500">Aucun superviseur enregistré. <a href="{{ route('superviseurs.create') }}" class="font-medium text-primary-600">En créer un</a></p>
            @endif
            <x-input-error :messages="$errors->get('superviseur_id')" class="mt-2" />
        </div>

        {{-- Statut --}}
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Statut</h2>

            <div class="mt-4 space-y-3">
                @foreach (\App\Models\Site::STATUTS as $valeur => $statut)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 hover:bg-gray-50 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                        <input type="radio" name="statut" value="{{ $valeur }}" class="text-primary-600 focus:ring-primary-500"
                               @checked(old('statut', $site->statut) === $valeur)>
                        <x-badge :color="$statut['color']">{{ $statut['label'] }}</x-badge>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('statut')" class="mt-2" />

            <div class="mt-5">
                <x-input-label for="date_ouverture" value="Date d'ouverture" />
                <x-text-input id="date_ouverture" name="date_ouverture" type="date" class="mt-1 block w-full"
                              :value="old('date_ouverture', $site->date_ouverture?->format('Y-m-d'))" :max="now()->format('Y-m-d')" />
                <x-input-error :messages="$errors->get('date_ouverture')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ $site->exists ? route('sites.show', $site) : route('sites.index') }}">
                <x-secondary-button>Annuler</x-secondary-button>
            </a>
            <x-primary-button>{{ $site->exists ? 'Enregistrer' : 'Créer le site' }}</x-primary-button>
        </div>
    </div>
</div>
