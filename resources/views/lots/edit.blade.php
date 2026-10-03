<x-app-layout>
    <x-slot name="title">Modifier {{ $lot->code }}</x-slot>
    <x-slot name="header">Modifier le lot {{ $lot->code }}</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('lots.index') }}" class="hover:text-primary-600">Lots</a> /
        <a href="{{ route('lots.show', $lot) }}" class="hover:text-primary-600">{{ $lot->code }}</a> /
        <span class="text-gray-700">Modifier</span>
    </nav>

    <form method="POST" action="{{ route('lots.update', $lot) }}">
        @csrf
        @method('PUT')
        @include('lots._form')
    </form>
</x-app-layout>
