<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // proforma | definitive
            $table->string('numero')->unique();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('montant_total', 14, 2)->default(0);
            $table->string('statut')->default('Emise');
            $table->timestamps();
        });

        Schema::create('facture_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained('factures')->cascadeOnDelete();
            $table->string('designation');
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 14, 2);
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facture_lignes');
        Schema::dropIfExists('factures');
    }
};
