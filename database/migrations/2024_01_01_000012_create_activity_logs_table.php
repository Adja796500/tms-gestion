<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('utilisateur_nom')->nullable();
            $table->string('utilisateur_profil')->nullable();
            $table->string('action'); // connexion, deconnexion, creation, modification, suppression, impression
            $table->string('module'); // classe Eloquent concernee (Vente, Client, AccessRule...) ou AUTH
            $table->string('libelle')->nullable();
            $table->unsignedBigInteger('sujet_id')->nullable();
            $table->json('donnees_avant')->nullable();
            $table->json('donnees_apres')->nullable();
            $table->string('ip')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['module', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
