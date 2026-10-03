<?php

namespace App\Providers;

use App\Models\Agent;
use App\Models\Superviseur;
use Illuminate\Database\Eloquent\Relations\Relation;
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
    }
}
