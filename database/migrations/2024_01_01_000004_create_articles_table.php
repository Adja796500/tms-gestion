<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('designation');
            $table->string('categorie')->nullable();
            $table->string('unite')->nullable();
            $table->decimal('prix_achat', 14, 2)->nullable();
            $table->decimal('prix_vente', 14, 2)->default(0);
            $table->integer('stock_initial')->default(0);
            $table->integer('stock_minimum')->default(0);
            $table->integer('stock_disponible')->default(0);
            $table->timestamps();

            $table->index('designation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
