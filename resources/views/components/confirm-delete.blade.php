@props(['action', 'name', 'title' => 'Confirmer la suppression', 'message' => 'Cette action est irréversible.'])

{{-- Bouton déclencheur (contenu du slot) + fenêtre de confirmation --}}
<span x-data @click.prevent="$dispatch('open-modal', '{{ $name }}')">
    {{ $slot }}
</span>

<x-modal :name="$name" maxWidth="md" focusable>
    <form method="POST" action="{{ $action }}" class="p-6">
        @csrf
        @method('DELETE')

        <div class="flex gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                <x-icon name="alert" class="h-5 w-5" />
            </span>
            <div>
                <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>
                <p class="mt-1 text-sm text-gray-600">{{ $message }}</p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-secondary-button x-on:click="$dispatch('close')">Annuler</x-secondary-button>
            <x-danger-button>Supprimer</x-danger-button>
        </div>
    </form>
</x-modal>
