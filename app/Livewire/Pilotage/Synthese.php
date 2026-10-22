<?php

namespace App\Livewire\Pilotage;

use App\Support\Finances;
use Livewire\Component;

class Synthese extends Component
{
    public function render(Finances $finances)
    {
        $resultat = $finances->resultat();
        $mensuel = $finances->syntheseMensuelle();

        return view('livewire.pilotage.synthese', [
            'resultat' => $resultat,
            'taux' => $finances->taux(),
            'repartition' => $finances->repartition($resultat['benefice']),
            'mensuel' => $mensuel,
            'totalAcquisitions' => $mensuel->sum('acquisitions'),
            'parCategorie' => $finances->parCategorie(),
        ])->layout('layouts.app', ['title' => 'Synthèse']);
    }
}
