<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reprend deux comportements du classeur Excel :
     * - chaque vente cree automatiquement une ligne "Entree" dans le JOURNAL
     *   (colonne technique idVente) : ici, une cle vente_id sur ecritures_journal,
     *   supprimee en cascade avec la vente ;
     * - le formulaire SERVICES (FmDonneesServices) : nouveau module SERVICES dans
     *   access_rules, avec la meme matrice que le journal comptable + Commercial.
     */
    private const PROFILS_LECTURE_SEULE = ['Directeur', 'Consultation'];

    private const DROITS_SERVICES = [
        'Administrateur' => 'modif',
        'Directeur' => 'lecture',
        'Comptable' => 'modif',
        'Caissier' => 'modif',
        'Commercial' => 'modif',
        'Consultation' => 'lecture',
    ];

    public function up(): void
    {
        Schema::table('ecritures_journal', function (Blueprint $table) {
            $table->foreignId('vente_id')->nullable()->after('utilisateur_id')->constrained('ventes')->cascadeOnDelete();
        });

        foreach (DB::table('entreprises')->pluck('id') as $entrepriseId) {
            foreach (User::PROFILS as $role) {
                $droit = self::DROITS_SERVICES[$role] ?? null;

                DB::table('access_rules')->updateOrInsert(
                    ['entreprise_id' => $entrepriseId, 'module' => 'SERVICES', 'role' => $role],
                    [
                        'module_label' => 'SERVICES',
                        'autorise' => $droit !== null,
                        'peut_modifier' => $droit === 'modif' && ! in_array($role, self::PROFILS_LECTURE_SEULE, true),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $this->reprendreVentesExistantes($entrepriseId);
        }
    }

    /**
     * Les ventes deja saisies avant cette migration recoivent aussi leur ligne de
     * journal, pour que le Journal des transactions soit complet des le depart.
     */
    private function reprendreVentesExistantes(int $entrepriseId): void
    {
        $ventes = DB::table('ventes')
            ->leftJoin('clients', 'clients.id', '=', 'ventes.client_id')
            ->where('ventes.entreprise_id', $entrepriseId)
            ->select('ventes.*', 'clients.nom as client_nom')
            ->get();

        if ($ventes->isEmpty()) {
            return;
        }

        $categorieVenteId = DB::table('categories_activite')
            ->where('entreprise_id', $entrepriseId)
            ->where('nom', 'Vente')
            ->value('id');

        if (! $categorieVenteId) {
            $categorieVenteId = DB::table('categories_activite')->insertGetId([
                'entreprise_id' => $entrepriseId,
                'nom' => 'Vente',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($ventes as $vente) {
            $nbArticles = DB::table('vente_lignes')->where('vente_id', $vente->id)->count();
            $moyen = $vente->mode_paiement ?: 'Especes';

            DB::table('ecritures_journal')->insert([
                'entreprise_id' => $entrepriseId,
                'date' => $vente->date,
                'categorie_activite_id' => $categorieVenteId,
                'type' => 'Entree',
                'description' => 'Vente - '.($vente->client_nom ?? 'Client').' ('.$nbArticles.' article(s))',
                'montant' => $vente->total,
                'moyen_paiement' => $moyen,
                'compte' => in_array($moyen, ['Cheque', 'Virement', 'Mobile Money', 'Orange Money', 'Moov Money'], true) ? 'Banque' : 'Caisse',
                'vente_id' => $vente->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('ecritures_journal')->whereNotNull('vente_id')->delete();

        Schema::table('ecritures_journal', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vente_id');
        });

        DB::table('access_rules')->where('module', 'SERVICES')->delete();
    }
};
