<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * L'ecran Commandes propose le statut "Executee" (comme la feuille COMMANDES du
     * classeur : En attente, En cours, Executee, Livree, Annulee), mais la contrainte
     * de la base ne l'autorisait pas : choisir ce statut provoquait une erreur.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE commandes DROP CONSTRAINT IF EXISTS commandes_statut_check');
        DB::statement("ALTER TABLE commandes ADD CONSTRAINT commandes_statut_check CHECK (statut IN ('En_attente', 'En_cours', 'Executee', 'Livree', 'Annulee'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::table('commandes')->where('statut', 'Executee')->update(['statut' => 'Livree']);
        DB::statement('ALTER TABLE commandes DROP CONSTRAINT IF EXISTS commandes_statut_check');
        DB::statement("ALTER TABLE commandes ADD CONSTRAINT commandes_statut_check CHECK (statut IN ('En_attente', 'En_cours', 'Livree', 'Annulee'))");
    }
};
