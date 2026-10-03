<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        @include('partials.personne-champs', ['personne' => $superviseur])
    </div>

    <div class="space-y-6">
        @include('partials.personne-statut', [
            'personne' => $superviseur,
            'aideTaux' => 'Proposé automatiquement comme commission superviseur lors de la création d\'un lot.',
        ])

        {{-- Sites supervisés --}}
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Sites supervisés</h2>
            <p class="mt-1 text-sm text-gray-500">Cochez les sites dont il est responsable.</p>

            @php $coches = collect(old('sites', $sitesSelectionnes))->map(fn ($id) => (int) $id)->all(); @endphp

            @if ($sites->isEmpty())
                <p class="mt-4 text-sm text-gray-500">Aucun site enregistré. <a href="{{ route('sites.create') }}" class="font-medium text-primary-600">Créer un site</a></p>
            @else
                <div class="mt-4 max-h-72 space-y-1 overflow-y-auto pe-1">
                    @foreach ($sites as $site)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg px-2 py-2 hover:bg-gray-50">
                            <input type="checkbox" name="sites[]" value="{{ $site->id }}" class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                   @checked(in_array($site->id, $coches, true))>
                            <span class="min-w-0 text-sm">
                                <span class="block font-medium text-gray-900">{{ $site->nom }}</span>
                                @if ($site->superviseur && $site->superviseur->id !== $superviseur->id)
                                    <span class="block text-xs text-amber-700">Actuellement : {{ $site->superviseur->nom_complet }}</span>
                                @elseif (! $site->superviseur)
                                    <span class="block text-xs text-gray-400">Sans superviseur</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
            <x-input-error :messages="$errors->get('sites')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ $superviseur->exists ? route('superviseurs.show', $superviseur) : route('superviseurs.index') }}">
                <x-secondary-button>Annuler</x-secondary-button>
            </a>
            <x-primary-button>{{ $superviseur->exists ? 'Enregistrer' : 'Créer le superviseur' }}</x-primary-button>
        </div>
    </div>
</div>
