<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Facture;
use App\Models\ParametreEntreprise;
use App\Support\DocumentPdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public const MODULE_PAR_TYPE = [
        Facture::TYPE_PROFORMA => 'FACTURE_PROFORMA',
        Facture::TYPE_DEFINITIVE => 'FACTURE_DEFINITIVE',
        Facture::TYPE_BORDEREAU => 'BORDEREAU_LIVRAISON',
        Facture::TYPE_BON_COMMANDE => 'BON_COMMANDE',
    ];

    /**
     * Impression (et PDF via le navigateur) des bordereaux de livraison et bons de
     * commande, sur le meme gabarit que les factures.
     */
    public function imprimer(Request $request, Facture $document): View
    {
        $module = match ($document->type) {
            Facture::TYPE_BORDEREAU => 'BORDEREAU_LIVRAISON',
            Facture::TYPE_BON_COMMANDE => 'BON_COMMANDE',
            default => abort(404),
        };

        abort_unless($request->user()->peutAccederA($module), 403);

        $document->load(['client', 'fournisseur', 'lignes', 'factureLiee']);

        ActivityLog::enregistrer('impression', $module, $document->id, null, null, $document->numero);

        return view('documents.print', [
            'document' => $document,
            'parametres' => ParametreEntreprise::where('entreprise_id', $document->entreprise_id)->first(),
        ]);
    }

    /**
     * Telechargement du PDF genere cote serveur (meme fichier que celui envoye par email).
     */
    public function pdf(Request $request, Facture $document): \Illuminate\Http\Response
    {
        $module = self::MODULE_PAR_TYPE[$document->type] ?? abort(404);
        abort_unless($request->user()->peutAccederA($module), 403);

        ActivityLog::enregistrer('telechargement_pdf', $module, $document->id, null, null, $document->numero);

        return response(DocumentPdf::contenu($document), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.DocumentPdf::nomFichier($document).'"',
        ]);
    }
}
