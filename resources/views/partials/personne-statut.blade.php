{{-- Statut + taux de commission par défaut. Variables : $personne, $aideTaux --}}
<div class="card p-6">
    <h2 class="text-base font-semibold text-gray-900">Statut & commission</h2>

    <div class="mt-4 grid grid-cols-2 gap-3">
        @foreach ($personne::statuts() as $valeur => $statut)
            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-3 py-2.5 hover:bg-gray-50 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                <input type="radio" name="statut" value="{{ $valeur }}" class="text-primary-600 focus:ring-primary-500" @checked(old('statut', $personne->statut) === $valeur)>
                <x-badge :color="$statut['color']">{{ $statut['label'] }}</x-badge>
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('statut')" class="mt-2" />

    <div class="mt-5">
        <x-input-label for="taux_commission" value="Taux de commission par défaut" />
        <div class="relative mt-1">
            <x-text-input id="taux_commission" name="taux_commission" type="number" step="0.01" min="0" max="100" class="block w-full pe-10"
                          :value="old('taux_commission', $personne->taux_commission ?? 0)" />
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pe-3 text-sm text-gray-500">%</span>
        </div>
        <p class="mt-1 text-xs text-gray-500">{{ $aideTaux }}</p>
        <x-input-error :messages="$errors->get('taux_commission')" class="mt-2" />
    </div>
</div>
