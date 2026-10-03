<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        @include('partials.personne-champs', ['personne' => $agent])
    </div>

    <div class="space-y-6">
        {{-- Affectation --}}
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Affectation</h2>
            <p class="mt-1 text-sm text-gray-500">Un agent travaille sur un seul site. Son superviseur est celui du site.</p>

            <div class="mt-4">
                <x-input-label for="site_id" value="Site" />
                <x-select id="site_id" name="site_id" class="mt-1 block w-full">
                    <option value="">— Aucun site —</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}" @selected((string) old('site_id', $agent->site_id) === (string) $site->id)>
                            {{ $site->nom }}{{ $site->superviseur ? ' — sup. '.$site->superviseur->nom_complet : '' }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('site_id')" class="mt-2" />
            </div>
        </div>

        @include('partials.personne-statut', [
            'personne' => $agent,
            'aideTaux' => 'Proposé automatiquement comme commission agent lors de la création d\'un lot.',
        ])

        <div class="flex items-center justify-end gap-3">
            <a href="{{ $agent->exists ? route('agents.show', $agent) : route('agents.index') }}">
                <x-secondary-button>Annuler</x-secondary-button>
            </a>
            <x-primary-button>{{ $agent->exists ? 'Enregistrer' : "Créer l'agent" }}</x-primary-button>
        </div>
    </div>
</div>
