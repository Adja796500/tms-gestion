<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entreprises', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // La plateforme etait jusque-la mono-entreprise : ENTREPRISE DAME devient
        // la premiere entreprise cliente, ses donnees existantes lui sont rattachees
        // par les migrations suivantes.
        DB::table('entreprises')->insert([
            'nom' => 'ENTREPRISE DAME',
            'actif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('entreprises');
    }
};
