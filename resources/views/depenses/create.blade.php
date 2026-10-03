<x-app-layout>
    <x-slot name="title">Nouvelle dépense</x-slot>
    <x-slot name="header">Nouvelle dépense</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('depenses.index') }}" class="hover:text-primary-600">Dépenses</a> / <span class="text-gray-700">Nouvelle</span>
    </nav>

    <form method="POST" action="{{ route('depenses.store') }}" enctype="multipart/form-data">
        @csrf
        @include('depenses._form')
    </form>
</x-app-layout>
