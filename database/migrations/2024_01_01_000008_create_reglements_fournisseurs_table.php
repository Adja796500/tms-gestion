<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglements_fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->date('date');
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->cascadeOnDelete();
            $table->foreignId('achat_id')->nullable()->constrained('achats')->nullOnDelete();
            $table->decimal('montant', 14, 2);
            $table->string('mode_paiement')->default('Especes');
            $table->string('observations')->nullable();
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglements_fournisseurs');
    }
};
