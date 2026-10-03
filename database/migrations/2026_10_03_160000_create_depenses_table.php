<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();                       // ex : DEP-001
            $table->foreignId('site_id')->nullable()->constrained('sites'); // null = dépense générale (tous les sites)
            $table->date('date_depense')->index();
            $table->string('categorie', 30)->index();                   // voir Depense::CATEGORIES
            $table->string('libelle', 150);
            $table->unsignedBigInteger('montant');                      // GNF
            $table->string('mode', 20)->default('especes');             // mêmes modes que les paiements
            $table->string('fournisseur', 120)->nullable();             // à qui l'argent a été versé
            $table->string('reference', 80)->nullable();                // n° de facture / transaction
            $table->string('justificatif')->nullable();                 // chemin du fichier (photo / PDF)
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
