<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forfaits', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();                 // ex : FOR-001
            $table->string('nom', 80)->unique();                  // ex : Pass 1 heure
            $table->unsignedInteger('duree_valeur');              // ex : 1
            $table->string('duree_unite', 10);                    // minute | heure | jour | semaine | mois
            $table->unsignedInteger('duree_minutes')->index();    // calculé, sert au tri
            $table->unsignedSmallInteger('nb_appareils')->default(1);
            $table->unsignedInteger('prix');                      // prix de base en GNF
            $table->string('couleur', 20)->default('primary');    // couleur du badge
            $table->string('description')->nullable();
            $table->string('statut', 20)->default('actif')->index(); // actif | inactif
            $table->timestamps();
            $table->softDeletes();
        });

        // Prix particulier d'un forfait sur un site (sinon le prix de base s'applique)
        Schema::create('forfait_site', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forfait_id')->constrained('forfaits')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->unsignedInteger('prix');
            $table->timestamps();
            $table->unique(['forfait_id', 'site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forfait_site');
        Schema::dropIfExists('forfaits');
    }
};
