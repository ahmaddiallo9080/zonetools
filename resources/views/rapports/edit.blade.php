<x-app-layout>
    <x-slot name="title">Modifier {{ $rapport->code }}</x-slot>
    <x-slot name="header">Modifier le rapport {{ $rapport->code }}</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('rapports.index') }}" class="hover:text-primary-600">Rapports</a> /
        <a href="{{ route('rapports.show', $rapport) }}" class="hover:text-primary-600">{{ $rapport->code }}</a> /
        <span class="text-gray-700">Modifier</span>
    </nav>

    <form method="POST" action="{{ route('rapports.update', $rapport) }}">
        @csrf
        @method('PUT')
        @include('rapports._form')
    </form>
</x-app-layout>
