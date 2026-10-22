<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les numeros de documents et les codes articles etaient uniques sur TOUTE la
     * plateforme alors que chaque entreprise a ses propres compteurs : la premiere
     * vente d'une nouvelle entreprise (VEN-2026-000001) entrait en collision avec
     * celle d'une autre et plantait. Ils deviennent uniques par entreprise.
     * (Le nom d'utilisateur reste unique sur toute la plateforme : il sert a se connecter.)
     */
    private const TABLES = [
        'achats' => 'numero',
        'ventes' => 'numero',
        'paiements_clients' => 'numero',
        'reglements_fournisseurs' => 'numero',
        'factures' => 'numero',
        'recus' => 'numero',
        'commandes' => 'numero',
        'articles' => 'code',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $colonne) {
            Schema::table($table, function (Blueprint $t) use ($table, $colonne) {
                $t->dropUnique("{$table}_{$colonne}_unique");
                $t->unique(['entreprise_id', $colonne]);
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table => $colonne) {
            Schema::table($table, function (Blueprint $t) use ($colonne) {
                $t->dropUnique(['entreprise_id', $colonne]);
                $t->unique($colonne);
            });
        }
    }
};
