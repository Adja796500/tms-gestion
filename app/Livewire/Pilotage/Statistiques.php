<?php

namespace App\Livewire\Pilotage;

use App\Models\Acquisition;
use App\Support\Finances;
use Livewire\Component;

class Statistiques extends Component
{
    public function render(Finances $finances)
    {
        $mensuel = $finances->syntheseMensuelle();
        $resultat = $finances->resultat();

        $meilleur = $mensuel->sortByDesc('benefice')->first();
        $plusFaible = $mensuel->sortBy('benefice')->first();

        return view('livewire.pilotage.statistiques', [
            'meilleur' => $meilleur,
            'plusFaible' => $plusFaible,
            'beneficeMoyen' => $mensuel->isEmpty() ? 0 : $mensuel->avg('benefice'),
            'margeMoyenne' => $resultat['entrees'] > 0 ? $resultat['benefice'] / $resultat['entrees'] : 0,
            'nbTransactions' => $resultat['nb'],
            'nbAcquisitions' => Acquisition::where('type', 'Acquisition')->count(),
            'beneficeTotal' => $resultat['benefice'],
            'parCategorie' => $finances->parCategorie()->sortByDesc('benefice')->values(),
            'topAcquisitions' => Acquisition::orderByDesc('montant')->limit(10)->get(),
            'mensuel' => $mensuel,
        ])->layout('layouts.app', ['title' => 'Statistiques & indicateurs clés']);
    }
}
