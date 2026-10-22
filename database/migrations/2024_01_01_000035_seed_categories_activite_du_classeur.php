<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Liste "Categories d'activite" de la feuille PARAMETRES. Le Rapport mensuel et
     * le module Services les affichent toutes, meme a zero : on cree celles qui
     * manquent, sans toucher aux categories deja personnalisees.
     */
    private const CATEGORIES = ['ABA', 'Photographie', 'Infographie', 'Caligraphie', 'Sérigraphie', 'Vente', 'Autres'];

    public function up(): void
    {
        foreach (DB::table('entreprises')->pluck('id') as $entrepriseId) {
            $existantes = DB::table('categories_activite')->where('entreprise_id', $entrepriseId)->pluck('nom')->all();

            foreach (array_diff(self::CATEGORIES, $existantes) as $nom) {
                DB::table('categories_activite')->insert([
                    'entreprise_id' => $entrepriseId,
                    'nom' => $nom,
                    'actif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Donnees de reference : on ne supprime rien (elles peuvent etre utilisees par des ecritures).
    }
};
