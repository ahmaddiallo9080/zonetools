<x-app-layout>
    <x-slot name="title">Nouveau lot</x-slot>
    <x-slot name="header">Nouveau lot de tickets</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('lots.index') }}" class="hover:text-primary-600">Lots</a> / <span class="text-gray-700">Nouveau</span>
    </nav>

    <form method="POST" action="{{ route('lots.store') }}">
        @csrf
        @include('lots._form')
    </form>
</x-app-layout>
