<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecritures_journal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('categorie_activite_id')->nullable()->constrained('categories_activite')->nullOnDelete();
            $table->enum('type', ['Entree', 'Depense']);
            $table->string('description');
            $table->decimal('montant', 14, 2);
            $table->string('moyen_paiement'); // Especes, Cheque, Virement, Mobile Money
            $table->string('compte');         // Caisse | Banque, derive de moyen_paiement
            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecritures_journal');
    }
};
