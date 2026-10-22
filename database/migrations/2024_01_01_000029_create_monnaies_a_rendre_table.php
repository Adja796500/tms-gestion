<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monnaies_a_rendre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->nullable()->constrained('entreprises')->nullOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('vente_id')->nullable()->constrained('ventes')->nullOnDelete();
            $table->date('date');
            $table->decimal('montant', 14, 2);
            $table->string('statut')->default('due'); // due | reglee
            $table->timestamp('reglee_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monnaies_a_rendre');
    }
};
