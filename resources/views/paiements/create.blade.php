<x-app-layout>
    <x-slot name="title">Nouveau paiement</x-slot>
    <x-slot name="header">Nouveau paiement</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('versements.index') }}" class="hover:text-primary-600">Versements & commissions</a> / <span class="text-gray-700">Nouveau paiement</span>
    </nav>

    <form method="POST" action="{{ route('paiements.store') }}">
        @csrf
        @include('paiements._form')
    </form>
</x-app-layout>
