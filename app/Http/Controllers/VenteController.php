<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Vente;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VenteController extends Controller
{
    public function imprimer(Request $request, Vente $vente): View
    {
        abort_unless($request->user()->peutAccederA(\App\Livewire\Ventes::MODULES_CAISSE), 403);

        $vente->load(['client', 'lignes.article', 'monnaiesARendre']);

        ActivityLog::enregistrer('impression', 'VENTES', $vente->id, null, null, $vente->numero);

        return view('ventes.print', ['vente' => $vente]);
    }
}
