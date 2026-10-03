<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();          // ex : SITE-001
            $table->string('nom', 120)->unique();
            $table->string('ville', 80)->nullable();
            $table->string('quartier', 80)->nullable();
            $table->string('adresse')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->date('date_ouverture')->nullable();
            $table->string('statut', 20)->default('actif')->index(); // actif | maintenance | inactif
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
