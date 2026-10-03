<x-app-layout>
    <x-slot name="title">Nouvel agent</x-slot>
    <x-slot name="header">Nouvel agent</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('agents.index') }}" class="hover:text-primary-600">Agents</a> / <span class="text-gray-700">Nouveau</span>
    </nav>

    <form method="POST" action="{{ route('agents.store') }}">
        @csrf
        @include('agents._form')
    </form>
</x-app-layout>
