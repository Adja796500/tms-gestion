<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Recu;
use Illuminate\View\View;

class RecuController extends Controller
{
    public function imprimer(Recu $recu): View
    {
        $recu->load('paiementClient.client');

        ActivityLog::enregistrer('impression', 'RECU', $recu->id, null, null, $recu->numero);

        return view('recus.print', ['recu' => $recu]);
    }
}
