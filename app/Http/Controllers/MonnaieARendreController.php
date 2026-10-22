<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\MonnaieARendre;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonnaieARendreController extends Controller
{
    public function imprimer(Request $request, MonnaieARendre $monnaieARendre): View
    {
        abort_unless($request->user()->peutAccederA(\App\Livewire\Ventes::MODULES_CAISSE), 403);

        $monnaieARendre->load(['client', 'vente']);

        ActivityLog::enregistrer('impression', 'VENTES', $monnaieARendre->id, null, null, 'Monnaie a rendre #'.$monnaieARendre->id);

        return view('monnaies-a-rendre.print', ['monnaie' => $monnaieARendre]);
    }
}
