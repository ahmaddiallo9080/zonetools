<x-app-layout>
    <x-slot name="title">Nouveau forfait</x-slot>
    <x-slot name="header">Nouveau forfait</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('forfaits.index') }}" class="hover:text-primary-600">Forfaits</a> / <span class="text-gray-700">Nouveau</span>
    </nav>

    <form method="POST" action="{{ route('forfaits.store') }}">
        @csrf
        @include('forfaits._form')
    </form>
</x-app-layout>
