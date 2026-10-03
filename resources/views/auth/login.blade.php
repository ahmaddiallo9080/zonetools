<x-guest-layout>
    <h1 class="text-2xl font-bold text-gray-900">Connexion</h1>
    <p class="mt-1 text-sm text-gray-500">Accédez à votre espace de gestion.</p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Mot de passe" />
                @if (Route::has('password.request'))
                    <a class="text-sm font-medium text-primary-600 hover:text-primary-700" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
                @endif
            </div>
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center">
            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500" name="remember">
            <span class="ms-2 text-sm text-gray-600">Se souvenir de moi</span>
        </label>

        <x-primary-button class="w-full py-2.5">Se connecter</x-primary-button>
    </form>
</x-guest-layout>
