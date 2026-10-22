<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventes', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->date('date');
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 14, 2);
            $table->decimal('total', 14, 2);
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventes');
    }
};
