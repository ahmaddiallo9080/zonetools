<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();                       // ex : LOT-001
            $table->foreignId('site_id')->constrained('sites');
            $table->foreignId('agent_id')->constrained('agents');
            $table->foreignId('superviseur_id')->nullable()->constrained('superviseurs'); // superviseur du site au moment de la remise
            $table->date('date_remise')->index();
            $table->date('date_fin_prevue')->nullable();
            $table->decimal('taux_agent', 5, 2)->default(0);            // % du montant vendu
            $table->decimal('taux_superviseur', 5, 2)->default(0);      // % du montant vendu
            // Totaux recalculés à partir des lignes (pour les listes et statistiques)
            $table->unsignedInteger('quantite_totale')->default(0);
            $table->unsignedBigInteger('montant_total')->default(0);    // si tous les tickets sont vendus
            $table->string('statut', 20)->default('en_cours')->index(); // en_cours | termine | annule
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lot_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->foreignId('forfait_id')->constrained('forfaits');
            $table->unsignedInteger('quantite');
            $table->unsignedInteger('prix_unitaire');                   // prix figé à la création du lot
            $table->unsignedBigInteger('montant');                      // quantite × prix_unitaire
            $table->timestamps();
            $table->unique(['lot_id', 'forfait_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lot_lignes');
        Schema::dropIfExists('lots');
    }
};
