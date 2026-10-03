<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();              // ex : AGT-001
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete(); // un agent = un site
            $table->string('nom', 80);
            $table->string('prenom', 80);
            $table->string('telephone', 30);
            $table->string('telephone2', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('adresse')->nullable();
            $table->string('piece_identite', 60)->nullable();
            $table->date('date_embauche')->nullable();
            $table->decimal('taux_commission', 5, 2)->default(0); // % proposé par défaut sur les lots
            $table->string('statut', 20)->default('actif')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
