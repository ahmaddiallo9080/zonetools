@props([
    'show' => null,
    'edit' => null,
    'destroy' => null,
    'name' => null,
    'message' => 'Cette action est irréversible.',
])

{{-- Boutons d'action en icônes pour les lignes de tableau --}}
<div class="inline-flex items-center gap-1">
    @if ($show)
        <a href="{{ $show }}" title="Voir" aria-label="Voir"
            class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-500 hover:text-white">
            <x-icon name="eye" class="h-5 w-5" />
        </a>
    @endif

    @if ($edit)
        <a href="{{ $edit }}" title="Modifier" aria-label="Modifier"
            class="rounded-lg p-1.5 text-primary-500 hover:bg-primary-500 hover:text-white">
            <x-icon name="pencil" class="h-5 w-5" />
        </a>
    @endif

    @if ($destroy)
        <x-confirm-delete :action="$destroy" :name="$name" :message="$message">
            <button type="button" title="Supprimer" aria-label="Supprimer"
                class="rounded-lg p-1.5 text-red-500 hover:bg-red-500 hover:text-white">
                <x-icon name="trash" class="h-5 w-5" />
            </button>
        </x-confirm-delete>
    @endif
</div>
