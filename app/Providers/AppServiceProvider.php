<?php

namespace App\Providers;

use App\Models\Agent;
use App\Models\Superviseur;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Noms courts stockés en base pour les bénéficiaires des paiements
        Relation::enforceMorphMap([
            'agent' => Agent::class,
            'superviseur' => Superviseur::class,
        ]);

        // En production (hébergement mutualisé avec redirection vers public/),
        // on impose l'adresse du site : évite les liens en « /public/... »
        if ($this->app->environment('production') && config('app.url')) {
            URL::forceRootUrl(config('app.url'));
            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
