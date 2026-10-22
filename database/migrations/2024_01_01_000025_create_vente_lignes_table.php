<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vente_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_id')->constrained('ventes')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 14, 2);
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });

        // Chaque vente existante (1 article, saisie ligne par ligne) devient une
        // vente en-tete avec une seule ligne de panier.
        DB::table('ventes')->orderBy('id')->each(function ($vente) {
            DB::table('vente_lignes')->insert([
                'vente_id' => $vente->id,
                'article_id' => $vente->article_id,
                'quantite' => $vente->quantite,
                'prix_unitaire' => $vente->prix_unitaire,
                'total' => $vente->total,
                'created_at' => $vente->created_at,
                'updated_at' => $vente->updated_at,
            ]);
        });

        Schema::table('ventes', function (Blueprint $table) {
            $table->dropForeign(['article_id']);
            $table->dropColumn(['article_id', 'quantite', 'prix_unitaire']);
        });
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->foreignId('article_id')->nullable()->after('client_id')->constrained('articles')->cascadeOnDelete();
            $table->integer('quantite')->nullable()->after('article_id');
            $table->decimal('prix_unitaire', 14, 2)->nullable()->after('quantite');
        });

        DB::table('ventes')->orderBy('id')->each(function ($vente) {
            $premiereLigne = DB::table('vente_lignes')->where('vente_id', $vente->id)->orderBy('id')->first();

            if ($premiereLigne) {
                DB::table('ventes')->where('id', $vente->id)->update([
                    'article_id' => $premiereLigne->article_id,
                    'quantite' => $premiereLigne->quantite,
                    'prix_unitaire' => $premiereLigne->prix_unitaire,
                ]);
            }
        });

        Schema::dropIfExists('vente_lignes');
    }
};
