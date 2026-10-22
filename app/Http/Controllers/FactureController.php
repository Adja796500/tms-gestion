<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Facture;
use App\Models\ParametreEntreprise;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FactureController extends Controller
{
    public function imprimer(Request $request, Facture $facture): View
    {
        $module = $facture->type === Facture::TYPE_PROFORMA ? 'FACTURE_PROFORMA' : 'FACTURE_DEFINITIVE';

        abort_unless($request->user()->peutAccederA($module), 403);

        $facture->load(['client', 'lignes']);

        $parametres = ParametreEntreprise::where('entreprise_id', $facture->entreprise_id)->first();

        ActivityLog::enregistrer('impression', $module, $facture->id, null, null, $facture->numero);

        return view('factures.print', ['facture' => $facture, 'parametres' => $parametres]);
    }
}
