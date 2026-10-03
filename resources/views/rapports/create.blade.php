<x-app-layout>
    <x-slot name="title">Rapport du lot {{ $lot->code }}</x-slot>
    <x-slot name="header">Rapport de fin de lot</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('lots.index') }}" class="hover:text-primary-600">Lots</a> /
        <a href="{{ route('lots.show', $lot) }}" class="hover:text-primary-600">{{ $lot->code }}</a> /
        <span class="text-gray-700">Rapport</span>
    </nav>

    <form method="POST" action="{{ route('rapports.store', $lot) }}">
        @csrf
        @include('rapports._form')
    </form>
</x-app-layout>
