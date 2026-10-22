<?php

namespace Database\Seeders;

use App\Models\AccessRule;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccessRuleSeeder extends Seeder
{
    /**
     * Profils "consultation" : ils peuvent voir les modules qui leur sont ouverts
     * mais jamais creer/modifier/supprimer de donnees (Directeur = supervision,
     * Consultation = lecture seule explicite).
     */
    private const PROFILS_LECTURE_SEULE = ['Directeur', 'Consultation'];

    /**
     * Matrice de droits par defaut pour les 5 modules ajoutes en Phase 1 (Fondations) :
     * Configuration entreprise, Categories d'activite, Comptabilite (journal +
     * acquisitions), Commandes. Reglable ensuite sans code via l'ecran /parametres.
     *
     * Pour chaque module, seuls les roles ayant un droit sont listes :
     * 'modif' => autorise + peut_modifier (sauf si le role fait partie de
     * PROFILS_LECTURE_SEULE, qui reste toujours en lecture seule) ;
     * 'lecture' => autorise en lecture seule uniquement (ex: Magasinier sur
     * CATEGORIES_ACTIVITE, qui n'est pas un profil "lecture seule" en general
     * mais n'a pas besoin de modifier ce referentiel).
     * Les roles absents de la liste n'ont pas acces au module.
     */
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
        // Formulaire SERVICES du classeur (entrees/depenses ABA, Infographie,
        // Serigraphie...), cf. migration 000033.
        'SERVICES' => [
            'Administrateur' => 'modif',
            'Directeur' => 'lecture',
            'Comptable' => 'modif',
            'Caissier' => 'modif',
            'Commercial' => 'modif',
            'Consultation' => 'lecture',
        ],
    ];

    /**
     * Genere la matrice de droits par defaut pour une entreprise. Utilise a la fois
     * pour le seeding initial (ENTREPRISE DAME) et pour chaque nouvelle entreprise
     * creee depuis le panneau super-administrateur.
     */
    public function run(?int $entrepriseId = null): void
    {
        $entrepriseId ??= Entreprise::where('nom', 'ENTREPRISE DAME')->value('id');

        $donnees = DameDataFile::charger()['droits_acces'] ?? [];

        foreach ($donnees as $ligne) {
            $module = $ligne['Feuille'] ?? null;

            if (! $module) {
                continue;
            }

            foreach (User::PROFILS as $role) {
                $valeur = $ligne[$role] ?? 'Non';
                $autorise = strtolower((string) $valeur) === 'oui';

                AccessRule::updateOrCreate(
                    ['entreprise_id' => $entrepriseId, 'module' => $module, 'role' => $role],
                    [
                        'module_label' => $module,
                        'autorise' => $autorise,
                        'peut_modifier' => $autorise && ! in_array($role, self::PROFILS_LECTURE_SEULE, true),
                    ]
                );
            }
        }

        // Module de gestion des utilisateurs : reserve a l'Administrateur,
        // n'existait pas en tant que "feuille" dans le classeur d'origine.
        foreach (User::PROFILS as $role) {
            AccessRule::updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'module' => 'UTILISATEURS', 'role' => $role],
                [
                    'module_label' => 'UTILISATEURS',
                    'autorise' => $role === 'Administrateur',
                    'peut_modifier' => $role === 'Administrateur',
                ]
            );
        }

        // Journal d'activite : reserve a l'Administrateur (historique de toutes
        // les actions des autres utilisateurs).
        foreach (User::PROFILS as $role) {
            AccessRule::updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'module' => 'JOURNAL', 'role' => $role],
                [
                    'module_label' => 'JOURNAL',
                    'autorise' => $role === 'Administrateur',
                    'peut_modifier' => $role === 'Administrateur',
                ]
            );
        }

        // Modules ajoutes en Phase 1 (Fondations) : Configuration entreprise,
        // Categories d'activite, Comptabilite (journal + acquisitions), Commandes.
        foreach (self::MODULES_SUPPLEMENTAIRES as $module => $droitsParRole) {
            foreach (User::PROFILS as $role) {
                $droit = $droitsParRole[$role] ?? null;
                $autorise = $droit !== null;
                $peutModifier = $droit === 'modif' && ! in_array($role, self::PROFILS_LECTURE_SEULE, true);

                AccessRule::updateOrCreate(
                    ['entreprise_id' => $entrepriseId, 'module' => $module, 'role' => $role],
                    [
                        'module_label' => $module,
                        'autorise' => $autorise,
                        'peut_modifier' => $peutModifier,
                    ]
                );
            }
        }
    }
}
