@php use App\Support\Format; use App\Models\Depense; @endphp
<x-app-layout>
    <x-slot name="title">Dépenses</x-slot>
    <x-slot name="header">Dépenses</x-slot>
    <x-slot name="actions">
        <a href="{{ route('depenses.create', request()->filled('site') && request('site') !== 'general' ? ['site' => request('site')] : []) }}">
            <x-primary-button type="button"><span class="text-lg leading-none">+</span> Nouvelle dépense</x-primary-button>
        </a>
    </x-slot>

    @php $filtres = ['q', 'site', 'categorie', 'du', 'au']; @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Totaux --}}
        <div class="space-y-4">
            <div class="card bg-primary-600 p-5 text-white">
                <p class="text-sm text-primary-100">{{ request()->hasAny($filtres) ? 'Total (selon les filtres)' : 'Total de toutes les dépenses' }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums">{{ Format::gnf($total) }}</p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-gray-500">Dépenses de {{ now()->translatedFormat('F Y') }}</p>
                <p class="mt-1 text-xl font-bold tabular-nums text-gray-900">{{ Format::gnf($totalMois) }}</p>
            </div>
        </div>

        {{-- Répartition par catégorie --}}
        <div class="card p-6 lg:col-span-2">
            <h3 class="font-semibold text-gray-900">Répartition par catégorie</h3>
            <div class="mt-4">
                <x-graph.barres :lignes="$parCategorie->take(6)->map(fn ($montant, $cat) => [
                    'label' => Depense::CATEGORIES[$cat] ?? $cat,
                    'valeur' => (int) $montant,
                    'affiche' => Format::gnf($montant),
                    'detail' => $total ? str_replace('.', ',', round($montant * 100 / $total, 1)) . ' %' : null,
                    'lien' => route('depenses.index', array_merge(request()->only($filtres), ['categorie' => $cat])),
                ])->values()->all()" vide="Aucune dépense." />
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('depenses.index') }}" class="card mt-6 grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <input type="hidden" name="tri" value="{{ $tri }}">
        <input type="hidden" name="sens" value="{{ $sens }}">
        <x-text-input name="q" type="search" :value="request('q')" placeholder="Libellé, fournisseur, référence..." />
        <x-select name="site">
            <option value="">Tous les sites</option>
            <option value="general" @selected(request('site') === 'general')>Général (tous les sites)</option>
            @foreach ($sites as $site)
                <option value="{{ $site->id }}" @selected(request('site') == $site->id)>{{ $site->nom }}</option>
            @endforeach
        </x-select>
        <x-select name="categorie">
            <option value="">Toutes les catégories</option>
            @foreach (Depense::CATEGORIES as $cle => $label)
                <option value="{{ $cle }}" @selected(request('categorie') === $cle)>{{ $label }}</option>
            @endforeach
        </x-select>
        <div class="flex gap-2">
            <x-primary-button>Filtrer</x-primary-button>
            @if (request()->hasAny($filtres))
                <a href="{{ route('depenses.index') }}"><x-secondary-button>Réinitialiser</x-secondary-button></a>
            @endif
        </div>
        <div class="flex items-center gap-2"><span class="text-sm text-gray-500">Du</span><x-text-input name="du" type="date" :value="request('du')" class="w-full" /></div>
        <div class="flex items-center gap-2"><span class="text-sm text-gray-500">au</span><x-text-input name="au" type="date" :value="request('au')" class="w-full" /></div>
    </form>

    {{-- Liste --}}
    <div class="card mt-6 overflow-hidden">
        @if ($depenses->isEmpty())
            <div class="flex flex-col items-center px-6 py-16 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600"><x-icon name="receipt" class="h-6 w-6" /></span>
                @if (request()->hasAny($filtres))
                    <h3 class="mt-4 font-semibold text-gray-900">Aucune dépense ne correspond à votre recherche</h3>
                    <a href="{{ route('depenses.index') }}" class="mt-2 text-sm font-medium text-primary-600 hover:text-primary-700">Voir toutes les dépenses</a>
                @else
                    <h3 class="mt-4 font-semibold text-gray-900">Aucune dépense pour le moment</h3>
                    <p class="mt-1 text-sm text-gray-500">Internet, électricité, carburant, loyer... enregistrez ici chaque dépense de vos sites.</p>
                    <a href="{{ route('depenses.create') }}" class="mt-4"><x-primary-button type="button">+ Nouvelle dépense</x-primary-button></a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3"><x-sort-link column="date_depense" :tri="$tri" :sens="$sens">Date</x-sort-link></th>
                            <th class="px-4 py-3">Libellé</th>
                            <th class="px-4 py-3">Site</th>
                            <th class="px-4 py-3"><x-sort-link column="categorie" :tri="$tri" :sens="$sens">Catégorie</x-sort-link></th>
                            <th class="px-4 py-3">Mode</th>
                            <th class="px-4 py-3 text-right"><x-sort-link column="montant" :tri="$tri" :sens="$sens">Montant</x-sort-link></th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($depenses as $depense)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $depense->date_depense->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('depenses.show', $depense) }}" class="font-medium text-gray-900 hover:text-primary-600">{{ $depense->libelle }}</a>
                                    <span class="block text-xs text-gray-500">
                                        <span class="font-mono">{{ $depense->code }}</span>
                                        @if ($depense->fournisseur) · {{ $depense->fournisseur }} @endif
                                        @if ($depense->justificatif) · <x-icon name="paperclip" class="inline h-3.5 w-3.5" title="Justificatif joint" /> @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3 {{ $depense->site_id ? 'text-gray-700' : 'italic text-gray-500' }}">{{ $depense->site_id ? $depense->site->nom : 'Général' }}</td>
                                <td class="px-4 py-3"><x-badge color="gray">{{ $depense->categorie_label }}</x-badge></td>
                                <td class="px-4 py-3 text-gray-600">{{ $depense->mode_label }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-gray-900">{{ $depense->montant_affiche }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <x-table-actions
                                        :show="route('depenses.show', $depense)"
                                        :edit="route('depenses.edit', $depense)"
                                        :destroy="route('depenses.destroy', $depense)"
                                        :name="'supprimer-depense-'.$depense->id"
                                        :message="'La dépense « '.$depense->libelle.' » ('.$depense->montant_affiche.') sera supprimée.'" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($depenses->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">{{ $depenses->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
