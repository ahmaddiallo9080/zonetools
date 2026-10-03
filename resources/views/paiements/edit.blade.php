<x-app-layout>
    <x-slot name="title">Modifier {{ $paiement->code }}</x-slot>
    <x-slot name="header">Modifier le paiement {{ $paiement->code }}</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('paiements.index') }}" class="hover:text-primary-600">Paiements</a> /
        <a href="{{ route('paiements.show', $paiement) }}" class="hover:text-primary-600">{{ $paiement->code }}</a> /
        <span class="text-gray-700">Modifier</span>
    </nav>

    <form method="POST" action="{{ route('paiements.update', $paiement) }}">
        @csrf
        @method('PUT')
        @include('paiements._form')
    </form>
</x-app-layout>
