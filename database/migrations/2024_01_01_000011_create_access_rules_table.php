<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_rules', function (Blueprint $table) {
            $table->id();
            $table->string('module'); // code du module, ex: ARTICLES, VENTES...
            $table->string('module_label')->nullable();
            $table->string('role'); // Administrateur, Directeur, Comptable, Caissier, Magasinier, Commercial, Consultation
            $table->boolean('autorise')->default(false);
            $table->timestamps();

            $table->unique(['module', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_rules');
    }
};
