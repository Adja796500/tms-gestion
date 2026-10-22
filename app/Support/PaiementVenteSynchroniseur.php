<?php

namespace App\Support;

use App\Models\PaiementClient;
use App\Models\Vente;

/**
 * Une vente enregistree au comptoir avec un montant remis est payee sur le champ,
 * mais rien n'en gardait trace dans les Paiements clients (utilises par le solde
 * de la fiche client et par le module Creances clients) : une vente reglee
 * comptant apparaissait donc pour toujours comme "reste a payer". Ce
 * synchroniseur maintient un Paiement client miroir de chaque vente payee, pour
 * que le solde du client reflete la realite.
 */
class PaiementVenteSynchroniseur
{
    public static function synchroniser(Vente $vente): void
    {
        $paiementExistant = PaiementClient::where('vente_id', $vente->id)->first();

        if ($vente->montant_remis === null) {
            $paiementExistant?->delete();

            return;
        }

        PaiementClient::updateOrCreate(
            ['vente_id' => $vente->id],
            [
                // Explicite plutot que confie au hook du trait AppartientAUneEntreprise :
                // ce synchroniseur tourne aussi hors contexte HTTP authentifie (commande
                // artisan de rattrapage), ou le hook ne peut pas deviner l'entreprise.
                'entreprise_id' => $vente->entreprise_id,
                'numero' => $paiementExistant->numero
                    ?? NumeroGenerator::suivant('paiements_clients', 'PAI', $vente->entreprise_id),
                'date' => $vente->date,
                'client_id' => $vente->client_id,
                'montant' => $vente->total,
                'mode_paiement' => $vente->mode_paiement ?: 'Especes',
                'observations' => 'Encaissement automatique (vente au comptoir)',
            ]
        );
    }
}
