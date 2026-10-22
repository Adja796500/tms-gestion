<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recus', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('paiement_client_id')->constrained('paiements_clients')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('montant', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recus');
    }
};
