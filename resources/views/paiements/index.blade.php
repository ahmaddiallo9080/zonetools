<x-app-layout>
    <x-slot name="title">Historique des paiements</x-slot>
    <x-slot name="header">Historique des paiements</x-slot>
    <x-slot name="actions">
        <a href="{{ route('paiements.create', request()->only('beneficiaire')) }}"><x-primary-button type="button"><span class="text-lg leading-none">+</span> Nouveau paiement</x-primary-button></a>
    </x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('versements.index') }}" class="hover:text-primary-600">Versements & commissions</a> / <span class="text-gray-700">Paiements</span>
    </nav>

    @php $filtres = ['type', 'mode', 'beneficiaire', 'du', 'au']; @endphp

    <form method="GET" action="{{ route('paiements.index') }}" class="card grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-select name="beneficiaire">
            <option value="">Tous les bénéficiaires</option>
            @foreach ($beneficiaires as $groupe => $options)
                <optgroup label="{{ $groupe }}">
                    @foreach ($options as $cle => $nom)
                        <option value="{{ $cle }}" @selected(request('beneficiaire') === $cle)>{{ $nom }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </x-select>
        <x-select name="type">
            <option value="">Superviseurs et agents</option>
            @foreach (\App\Models\Paiement::TYPES as $cle => $label)
                <option value="{{ $cle }}" @selected(request('type') === $cle)>{{ $label }}s</option>
            @endforeach
        </x-select>
        <x-select name="mode">
            <option value="">Tous les modes</option>
            @foreach (\App\Models\Paiement::MODES as $cle => $label)
                <option value="{{ $cle }}" @selected(request('mode') === $cle)>{{ $label }}</option>
            @endforeach
        </x-select>
        <div class="flex gap-2">
            <x-primary-button>Filtrer</x-primary-button>
            @if (request()->hasAny($filtres))
                <a href="{{ route('paiements.index') }}"><x-secondary-button>Réinitialiser</x-secondary-button></a>
            @endif
        </div>
        <div class="flex items-center gap-2"><span class="text-sm text-gray-500">Du</span><x-text-input name="du" type="date" :value="request('du')" class="w-full" /></div>
        <div class="flex items-center gap-2"><span class="text-sm text-gray-500">au</span><x-text-input name="au" type="date" :value="request('au')" class="w-full" /></div>
        <div class="flex items-center justify-end rounded-lg bg-green-50 px-4 text-sm text-green-800 lg:col-span-2">
            Total : <span class="ms-2 text-lg font-bold">{{ \App\Support\Format::gnf($total) }}</span>
        </div>
    </form>

    <div class="card mt-6 overflow-hidden">
        @if ($paiements->isEmpty())
            <div class="flex flex-col items-center px-6 py-16 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600"><x-icon name="banknotes" class="h-6 w-6" /></span>
                <h3 class="mt-4 font-semibold text-gray-900">Aucun paiement</h3>
                <p class="mt-1 text-sm text-gray-500">Les paiements de commissions apparaîtront ici.</p>
            </div>
        @else
            @include('paiements._tableau', ['paiements' => $paiements])
            @if ($paiements->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">{{ $paiements->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
