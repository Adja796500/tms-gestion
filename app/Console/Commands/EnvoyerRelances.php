<?php

namespace App\Console\Commands;

use App\Models\ParametreEntreprise;
use App\Support\EnvoiDocument;
use App\Support\Relances;
use Illuminate\Console\Command;

/**
 * Relance automatique (planifiee chaque matin) des clients en retard de paiement,
 * pour chaque entreprise qui a active les relances dans sa configuration.
 */
class EnvoyerRelances extends Command
{
    protected $signature = 'relances:envoyer {--simulation : Liste les relances sans envoyer d\'email}';

    protected $description = 'Envoie par email les relances des factures impayées (entreprises ayant activé les relances)';

    public function handle(): int
    {
        $total = 0;

        foreach (ParametreEntreprise::withoutGlobalScopes()->where('relances_actives', true)->get() as $parametres) {
            foreach (Relances::aRelancer($parametres) as $facture) {
                $ligne = "{$parametres->nom_entreprise} : {$facture->numero} ({$facture->client?->nom}, {$facture->jours_retard} j, reste {$facture->reste})";

                if ($this->option('simulation')) {
                    $this->line('[simulation] '.$ligne);
                    $total++;

                    continue;
                }

                try {
                    EnvoiDocument::envoyer($facture, relance: true);
                    $this->info('Relance envoyée — '.$ligne);
                    $total++;
                } catch (\Throwable $e) {
                    report($e);
                    $this->error('Échec — '.$ligne.' : '.$e->getMessage());
                }
            }
        }

        $this->info("{$total} relance(s) traitée(s).");

        return self::SUCCESS;
    }
}
