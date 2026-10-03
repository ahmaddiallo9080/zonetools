{{-- Champs communs superviseur / agent. Variables : $personne, $codeSuggere (optionnel) --}}
<div class="card p-6">
    <h2 class="text-base font-semibold text-gray-900">Identité</h2>
    <p class="mt-1 text-sm text-gray-500">Les champs marqués d'un <span class="text-red-500">*</span> sont obligatoires.</p>

    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="prenom">Prénom <span class="text-red-500">*</span></x-input-label>
            <x-text-input id="prenom" name="prenom" type="text" class="mt-1 block w-full" :value="old('prenom', $personne->prenom)" required autofocus />
            <x-input-error :messages="$errors->get('prenom')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="nom">Nom <span class="text-red-500">*</span></x-input-label>
            <x-text-input id="nom" name="nom" type="text" class="mt-1 block w-full uppercase" :value="old('nom', $personne->nom)" required />
            <x-input-error :messages="$errors->get('nom')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="telephone">Téléphone <span class="text-red-500">*</span></x-input-label>
            <x-text-input id="telephone" name="telephone" type="tel" class="mt-1 block w-full" :value="old('telephone', $personne->telephone)" required placeholder="+224 6xx xx xx xx" />
            <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="telephone2" value="Second téléphone" />
            <x-text-input id="telephone2" name="telephone2" type="tel" class="mt-1 block w-full" :value="old('telephone2', $personne->telephone2)" />
            <x-input-error :messages="$errors->get('telephone2')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $personne->email)" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="code" value="Code" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full uppercase" :value="old('code', $personne->code)" :placeholder="$codeSuggere ?? ''" />
            <p class="mt-1 text-xs text-gray-500">Laissez vide pour générer automatiquement.</p>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="adresse" value="Adresse" />
            <x-text-input id="adresse" name="adresse" type="text" class="mt-1 block w-full" :value="old('adresse', $personne->adresse)" />
            <x-input-error :messages="$errors->get('adresse')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="piece_identite" value="Pièce d'identité" />
            <x-text-input id="piece_identite" name="piece_identite" type="text" class="mt-1 block w-full" :value="old('piece_identite', $personne->piece_identite)" placeholder="Ex : CNI 123456789" />
            <x-input-error :messages="$errors->get('piece_identite')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="date_embauche" value="Date d'embauche" />
            <x-text-input id="date_embauche" name="date_embauche" type="date" class="mt-1 block w-full"
                          :value="old('date_embauche', $personne->date_embauche?->format('Y-m-d'))" :max="now()->format('Y-m-d')" />
            <x-input-error :messages="$errors->get('date_embauche')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="notes" value="Notes" />
            <x-textarea id="notes" name="notes" class="mt-1 block w-full">{{ old('notes', $personne->notes) }}</x-textarea>
            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
        </div>
    </div>
</div>
