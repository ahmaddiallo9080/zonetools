@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
         class="no-print mb-6 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        <x-icon name="check" class="h-5 w-5 shrink-0" />
        <span class="flex-1">{{ session('success') }}</span>
        <button type="button" @click="show = false" class="text-green-600 hover:text-green-800"><x-icon name="x" class="h-4 w-4" /></button>
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="no-print mb-6 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <x-icon name="alert" class="h-5 w-5 shrink-0" />
        <span class="flex-1">{{ session('error') }}</span>
        <button type="button" @click="show = false" class="text-red-600 hover:text-red-800"><x-icon name="x" class="h-4 w-4" /></button>
    </div>
@endif
