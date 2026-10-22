<?php

namespace App\Livewire\Pilotage;

use App\Support\Finances;
use Carbon\CarbonImmutable;
use Livewire\Component;

class Tresorerie extends Component
{
    /** "Date a analyser" de la vue journaliere. */
    public string $dateAnalyse = '';

    public function mount(): void
    {
        $this->dateAnalyse = now()->toDateString();
    }

    public function render(Finances $finances)
    {
        $jour = CarbonImmutable::parse($this->dateAnalyse ?: now()->toDateString());

        return view('livewire.pilotage.tresorerie', [
            'soldes' => $finances->soldes(),
            'fluxJour' => $finances->flux($jour, $jour),
            'mensuel' => $finances->syntheseMensuelle(),
            'soldesInitiaux' => $finances->soldesInitiaux(),
        ])->layout('layouts.app', ['title' => 'Tableau de trésorerie']);
    }
}
