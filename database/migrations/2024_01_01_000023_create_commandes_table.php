<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();
            $table->string('numero')->unique();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->date('date');
            $table->string('designation');
            $table->integer('quantite');
            $table->decimal('montant', 14, 2);
            $table->date('date_livraison_prevue')->nullable();
            $table->enum('statut', ['En_attente', 'En_cours', 'Livree', 'Annulee'])->default('En_attente');
            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
