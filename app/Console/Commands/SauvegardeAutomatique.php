<?php

namespace App\Console\Commands;

use App\Models\Entreprise;
use App\Support\Sauvegarde;
use Illuminate\Console\Command;

/**
 * Sauvegarde de nuit (planifiee a 2 h) de chaque entreprise : remplace la
 * VerifierSauvegardeAutomatique du classeur, qui ne sauvegardait qu'a l'ouverture
 * du fichier et seulement tous les 7 jours.
 */
class SauvegardeAutomatique extends Command
{
    protected $signature = 'sauvegarde:automatique';

    protected $description = 'Crée la sauvegarde automatique de chaque entreprise et garde les '.Sauvegarde::A_CONSERVER.' plus récentes';

    public function handle(): int
    {
        foreach (Entreprise::query()->pluck('nom', 'id') as $id => $nom) {
            try {
                $chemin = Sauvegarde::automatique($id);
                $this->info("{$nom} : ".basename($chemin));
            } catch (\Throwable $e) {
                report($e);
                $this->error("{$nom} : échec — ".$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
