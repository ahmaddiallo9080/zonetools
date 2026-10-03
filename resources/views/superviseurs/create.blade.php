<x-app-layout>
    <x-slot name="title">Nouveau superviseur</x-slot>
    <x-slot name="header">Nouveau superviseur</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('superviseurs.index') }}" class="hover:text-primary-600">Superviseurs</a> / <span class="text-gray-700">Nouveau</span>
    </nav>

    <form method="POST" action="{{ route('superviseurs.store') }}">
        @csrf
        @include('superviseurs._form')
    </form>
</x-app-layout>
