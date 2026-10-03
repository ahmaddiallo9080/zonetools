<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('superviseurs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();              // ex : SUP-001
            $table->string('nom', 80);
            $table->string('prenom', 80);
            $table->string('telephone', 30);
            $table->string('telephone2', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('adresse')->nullable();
            $table->string('piece_identite', 60)->nullable();  // type + numéro de la pièce
            $table->date('date_embauche')->nullable();
            $table->decimal('taux_commission', 5, 2)->default(0); // % proposé par défaut sur les lots
            $table->string('statut', 20)->default('actif')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Chaque site est rattaché à un superviseur (un superviseur peut avoir plusieurs sites)
        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('superviseur_id')->nullable()->after('id')
                  ->constrained('superviseurs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('superviseur_id');
        });

        Schema::dropIfExists('superviseurs');
    }
};
