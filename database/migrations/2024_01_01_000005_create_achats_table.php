<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achats', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->date('date');
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->nullOnDelete();
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 14, 2);
            $table->decimal('total', 14, 2);
            $table->string('mode_reglement')->default('Comptant'); // Comptant | Credit
            $table->decimal('montant_regle', 14, 2)->default(0);
            $table->decimal('solde_restant', 14, 2)->default(0);
            $table->string('statut')->default('Paye (comptant)');
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achats');
    }
};
