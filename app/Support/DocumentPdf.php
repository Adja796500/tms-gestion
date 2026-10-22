<?php

namespace App\Support;

use App\Models\Facture;
use App\Models\ParametreEntreprise;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * Genere le PDF d'un document commercial (proforma, facture, bordereau, bon de
 * commande) cote serveur : utilise pour le telechargement, l'envoi par email et
 * les relances. Fonctionne aussi hors requete HTTP (relances de la nuit), d'ou
 * les requetes explicites par entreprise.
 */
class DocumentPdf
{
    public static function contenu(Facture $document): string
    {
        $document->loadMissing(['client', 'fournisseur', 'lignes', 'factureLiee', 'paiements']);

        $parametres = ParametreEntreprise::withoutGlobalScopes()->where('entreprise_id', $document->entreprise_id)->first();

        return Pdf::loadView('pdf.document', [
            'document' => $document,
            'parametres' => $parametres,
            'logo' => self::logoEnBase64($parametres),
        ])
            ->setPaper('a4')
            // N'integre que les caracteres utilises : PDF ~10 fois plus leger, adapte a l'email.
            ->setOption('isFontSubsettingEnabled', true)
            ->output();
    }

    public static function nomFichier(Facture $document): string
    {
        return Str::slug($document->libelleType().'-'.$document->numero.'-'.$document->nomPartenaire()).'.pdf';
    }

    /**
     * Le logo est integre directement dans le PDF : pas d'appel reseau, donc rendu
     * identique en local, en production et dans les taches de nuit.
     */
    private static function logoEnBase64(?ParametreEntreprise $parametres): ?string
    {
        $chemin = $parametres?->logo_path ? storage_path('app/public/'.$parametres->logo_path) : null;

        if (! $chemin || ! is_file($chemin)) {
            return null;
        }

        $type = mime_content_type($chemin) ?: 'image/png';

        return 'data:'.$type.';base64,'.base64_encode((string) file_get_contents($chemin));
    }
}
