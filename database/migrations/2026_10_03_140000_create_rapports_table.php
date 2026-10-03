<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();                          // ex : RAP-001
            $table->foreignId('lot_id')->unique()->constrained('lots');     // un rapport par lot
            $table->date('date_rapport')->index();
            // Totaux en tickets
            $table->unsignedInteger('quantite_vendue')->default(0);
            $table->unsignedInteger('quantite_defectueuse')->default(0);
            $table->unsignedInteger('quantite_rendue')->default(0);
            // Montants (GNF)
            $table->unsignedBigInteger('montant_vendu')->default(0);        // vendus × prix
            $table->unsignedBigInteger('commission_agent')->default(0);     // montant vendu × taux agent du lot
            $table->unsignedBigInteger('commission_superviseur')->default(0);
            $table->boolean('commission_agent_deduite')->default(true);     // l'agent a gardé sa commission
            $table->unsignedBigInteger('montant_attendu')->default(0);      // ce que l'agent doit verser
            $table->unsignedBigInteger('montant_verse')->default(0);        // ce qu'il a réellement versé
            $table->bigInteger('ecart')->default(0);                        // versé − attendu (négatif = manquant)
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('rapport_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rapport_id')->constrained('rapports')->cascadeOnDelete();
            $table->foreignId('lot_ligne_id')->constrained('lot_lignes')->cascadeOnDelete();
            $table->foreignId('forfait_id')->constrained('forfaits');
            $table->unsignedInteger('quantite_remise');
            $table->unsignedInteger('vendus');
            $table->unsignedInteger('defectueux');
            $table->unsignedInteger('rendus');
            $table->unsignedInteger('prix_unitaire');
            $table->unsignedBigInteger('montant_vendu');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapport_lignes');
        Schema::dropIfExists('rapports');
    }
};
