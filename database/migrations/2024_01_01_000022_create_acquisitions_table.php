<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();
            $table->date('date');
            $table->enum('type', ['Acquisition', 'Pret_Benefices', 'Depense_Reserve']);
            $table->string('designation');
            $table->decimal('montant', 14, 2);
            $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->nullOnDelete();
            $table->string('moyen_paiement');
            $table->string('compte');
            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisitions');
    }
};
