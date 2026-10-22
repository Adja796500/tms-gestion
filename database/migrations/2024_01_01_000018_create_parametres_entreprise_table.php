<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres_entreprise', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->unique()->constrained('entreprises')->cascadeOnDelete();
            $table->string('nom_entreprise')->nullable();
            $table->text('activites_texte')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('ville')->nullable();
            $table->string('adresse')->nullable();
            $table->string('regime_fiscal')->nullable();
            $table->string('division_fiscale')->nullable();
            $table->string('rccm')->nullable();
            $table->string('ifu')->nullable();
            $table->decimal('taux_investissement', 5, 2)->default(0);
            $table->decimal('taux_epargne', 5, 2)->default(0);
            $table->decimal('taux_loyer', 5, 2)->default(0);
            $table->decimal('taux_fonctionnement', 5, 2)->default(0);
            $table->decimal('solde_initial_caisse', 14, 2)->default(0);
            $table->decimal('solde_initial_banque', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres_entreprise');
    }
};
