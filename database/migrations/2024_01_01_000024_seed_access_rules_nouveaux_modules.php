<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Meme matrice que Database\Seeders\AccessRuleSeeder::MODULES_SUPPLEMENTAIRES,
     * dupliquee ici pour que cette migration reste autonome et stable dans le temps
     * (independante d'une eventuelle evolution future du seeder). Le backfill est
     * strictement limite a ces 5 modules : aucune autre ligne d'access_rules n'est
     * touchee, pour ne pas ecraser les personnalisations deja faites via l'ecran
     * /parametres sur les modules existants d'une entreprise deja en place.
     */
    private const PROFILS_LECTURE_SEULE = ['Directeur', 'Consultation'];

    private const MODULES_SUPPLEMENTAIRES = [
        'CONFIGURATION_ENTREPRISE' => [
            'Administrateur' => 'modif',
            'Directeur' => 'lecture',
            'Comptable' => 'modif',
            'Consultation' => 'lecture',
        ],
        'CATEGORIES_ACTIVITE' => [
            'Administrateur' => 'modif',
            'Directeur' => 'lecture',
            'Comptable' => 'modif',
            'Magasinier' => 'lecture',
            'Consultation' => 'lecture',
        ],
        'COMPTABILITE_JOURNAL' => [
            'Administrateur' => 'modif',
            'Directeur' => 'lecture',
            'Comptable' => 'modif',
            'Caissier' => 'modif',
            'Consultation' => 'lecture',
        ],
        'COMPTABILITE_ACQUISITIONS' => [
            'Administrateur' => 'modif',
            'Directeur' => 'lecture',
            'Comptable' => 'modif',
            'Consultation' => 'lecture',
        ],
        'COMMANDES' => [
            'Administrateur' => 'modif',
            'Directeur' => 'lecture',
            'Commercial' => 'modif',
            'Consultation' => 'lecture',
        ],
    ];

    public function up(): void
    {
        $entrepriseIds = DB::table('entreprises')->pluck('id');

        foreach ($entrepriseIds as $entrepriseId) {
            foreach (self::MODULES_SUPPLEMENTAIRES as $module => $droitsParRole) {
                foreach (User::PROFILS as $role) {
                    $droit = $droitsParRole[$role] ?? null;
                    $autorise = $droit !== null;
                    $peutModifier = $droit === 'modif' && ! in_array($role, self::PROFILS_LECTURE_SEULE, true);

                    DB::table('access_rules')->updateOrInsert(
                        ['entreprise_id' => $entrepriseId, 'module' => $module, 'role' => $role],
                        [
                            'module_label' => $module,
                            'autorise' => $autorise,
                            'peut_modifier' => $peutModifier,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('access_rules')->whereIn('module', array_keys(self::MODULES_SUPPLEMENTAIRES))->delete();
    }
};
