<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paiements de commissions du gérant vers un agent ou un superviseur
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();              // ex : PAY-001
            $table->string('beneficiaire_type', 20);           // agent | superviseur
            $table->unsignedBigInteger('beneficiaire_id');
            $table->date('date_paiement')->index();
            $table->unsignedBigInteger('montant');             // GNF
            $table->string('mode', 20)->default('especes');    // especes | orange_money | mtn_money | virement | autre
            $table->string('reference', 80)->nullable();       // n° de transaction
            $table->date('periode_du')->nullable();            // période couverte (facultatif)
            $table->date('periode_au')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['beneficiaire_type', 'beneficiaire_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
