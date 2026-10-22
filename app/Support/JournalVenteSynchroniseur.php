<?php

namespace App\Support;

use App\Models\CategorieActivite;
use App\Models\EcritureJournal;
use App\Models\Vente;

class JournalVenteSynchroniseur
{
    /**
     * Reprend ValiderVenteRapide du classeur : chaque vente ajoute (ou met a jour)
     * sa ligne "Entree" dans le Journal des transactions, categorie "Vente",
     * description "Vente - CLIENT (n article(s))". La suppression de la vente
     * supprime la ligne en cascade (cle vente_id).
     */
    public static function synchroniser(Vente $vente): EcritureJournal
    {
        $vente->loadMissing(['client', 'lignes']);

        $categorie = CategorieActivite::firstOrCreate(
            ['entreprise_id' => $vente->entreprise_id, 'nom' => 'Vente'],
            ['actif' => true]
        );

        $moyen = $vente->mode_paiement ?: 'Especes';

        return EcritureJournal::updateOrCreate(
            ['vente_id' => $vente->id],
            [
                'entreprise_id' => $vente->entreprise_id,
                'date' => $vente->date,
                'categorie_activite_id' => $categorie->id,
                'type' => 'Entree',
                'description' => 'Vente - '.($vente->client->nom ?? 'Client').' ('.$vente->lignes->count().' article(s))',
                'montant' => $vente->total,
                'moyen_paiement' => $moyen,
                'compte' => CompteResolver::depuisMoyenPaiement($moyen),
                'utilisateur_id' => auth()->id(),
            ]
        );
    }
}
