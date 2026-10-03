<x-app-layout>
    <x-slot name="title">Paramètres</x-slot>
    <x-slot name="header">Paramètres du compte</x-slot>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-2">
        <div class="card p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card p-6">
            @include('profile.partials.update-password-form')
        </div>
    </div>
</x-app-layout>
