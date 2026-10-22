<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements_clients', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->date('date');
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('vente_id')->nullable()->constrained('ventes')->nullOnDelete();
            $table->decimal('montant', 14, 2);
            $table->string('mode_paiement')->default('Especes');
            $table->string('observations')->nullable();
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_clients');
    }
};
