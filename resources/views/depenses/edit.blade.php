<x-app-layout>
    <x-slot name="title">Modifier {{ $depense->code }}</x-slot>
    <x-slot name="header">Modifier la dépense {{ $depense->code }}</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('depenses.index') }}" class="hover:text-primary-600">Dépenses</a> /
        <a href="{{ route('depenses.show', $depense) }}" class="hover:text-primary-600">{{ $depense->code }}</a> /
        <span class="text-gray-700">Modifier</span>
    </nav>

    <form method="POST" action="{{ route('depenses.update', $depense) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('depenses._form')
    </form>
</x-app-layout>
