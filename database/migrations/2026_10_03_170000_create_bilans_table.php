<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bilan mensuel clôturé d'un site : les chiffres sont figés au moment de la clôture
        Schema::create('bilans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites');
            $table->date('mois');                                   // 1er jour du mois (ex : 2026-09-01)
            $table->unsignedInteger('rapports')->default(0);
            $table->unsignedInteger('lots_remis')->default(0);
            $table->unsignedInteger('tickets_remis')->default(0);   // tickets des rapports du mois
            $table->unsignedInteger('vendus')->default(0);
            $table->unsignedInteger('defectueux')->default(0);
            $table->unsignedInteger('rendus')->default(0);
            $table->unsignedBigInteger('ventes')->default(0);
            $table->unsignedBigInteger('commission_agents')->default(0);
            $table->unsignedBigInteger('commission_superviseur')->default(0);
            $table->unsignedBigInteger('depenses')->default(0);
            $table->bigInteger('benefice')->default(0);
            $table->unsignedBigInteger('montant_verse')->default(0);
            $table->unsignedBigInteger('manquants')->default(0);
            $table->json('details')->nullable();                    // par forfait, par agent, par catégorie de dépense
            $table->text('observations')->nullable();
            $table->timestamp('cloture_le')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'mois']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bilans');
    }
};
