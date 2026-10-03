<x-app-layout>
    <x-slot name="title">Modifier {{ $agent->nom_complet }}</x-slot>
    <x-slot name="header">Modifier l'agent</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('agents.index') }}" class="hover:text-primary-600">Agents</a> /
        <a href="{{ route('agents.show', $agent) }}" class="hover:text-primary-600">{{ $agent->nom_complet }}</a> /
        <span class="text-gray-700">Modifier</span>
    </nav>

    <form method="POST" action="{{ route('agents.update', $agent) }}">
        @csrf
        @method('PUT')
        @include('agents._form')
    </form>
</x-app-layout>
