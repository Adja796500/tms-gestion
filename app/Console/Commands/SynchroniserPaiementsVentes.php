<?php

namespace App\Console\Commands;

use App\Models\Vente;
use App\Support\PaiementVenteSynchroniseur;
use Illuminate\Console\Command;

class SynchroniserPaiementsVentes extends Command
{
    protected $signature = 'ventes:synchroniser-paiements';

    protected $description = "Cree retroactivement un Paiement client pour chaque vente deja reglee au comptoir (montant remis renseigne) qui n'en a pas encore, pour que le solde des clients (fiche facture, Creances clients) reflete les ventes payees comptant";

    public function handle(): int
    {
        $ventes = Vente::whereNotNull('montant_remis')->get();

        $this->info("{$ventes->count()} vente(s) payee(s) au comptoir a verifier.");

        $bar = $this->output->createProgressBar($ventes->count());

        foreach ($ventes as $vente) {
            PaiementVenteSynchroniseur::synchroniser($vente);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Synchronisation terminee.');

        return self::SUCCESS;
    }
}
