<x-app-layout>
    <x-slot name="title">Modifier {{ $forfait->nom }}</x-slot>
    <x-slot name="header">Modifier le forfait</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('forfaits.index') }}" class="hover:text-primary-600">Forfaits</a> /
        <a href="{{ route('forfaits.show', $forfait) }}" class="hover:text-primary-600">{{ $forfait->nom }}</a> /
        <span class="text-gray-700">Modifier</span>
    </nav>

    <form method="POST" action="{{ route('forfaits.update', $forfait) }}">
        @csrf
        @method('PUT')
        @include('forfaits._form')
    </form>
</x-app-layout>
