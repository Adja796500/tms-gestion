<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Les entreprises creees depuis le super-administrateur avant cette version
     * n'avaient pas de fiche (parametres_entreprise) : on la cree avec leur nom et
     * la repartition du classeur. Les fiches existantes ne sont pas modifiees.
     */
    public function up(): void
    {
        $sansFiche = DB::table('entreprises')
            ->whereNotIn('id', DB::table('parametres_entreprise')->select('entreprise_id'))
            ->get(['id', 'nom']);

        foreach ($sansFiche as $entreprise) {
            DB::table('parametres_entreprise')->insert([
                'entreprise_id' => $entreprise->id,
                'nom_entreprise' => $entreprise->nom,
                'taux_investissement' => 55,
                'taux_epargne' => 10,
                'taux_loyer' => 5,
                'taux_fonctionnement' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Donnees de reference completees par l'entreprise ensuite : rien a defaire.
    }
};
