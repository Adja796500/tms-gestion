<?php

namespace App\Livewire\Pilotage;

use App\Models\CategorieActivite;
use App\Support\Finances;
use Carbon\CarbonImmutable;
use Livewire\Component;

class RapportMensuel extends Component
{
    /** Mois a analyser, au format AAAA-MM. */
    public string $mois = '';

    public function mount(): void
    {
        $this->mois = now()->format('Y-m');
    }

    public function render(Finances $finances)
    {
        $debut = CarbonImmutable::createFromFormat('Y-m-d', ($this->mois ?: now()->format('Y-m')).'-01')->startOfDay();
        $fin = $debut->endOfMonth();

        // Comme la feuille : toutes les categories apparaissent, meme a zero ce mois-ci.
        $detail = $finances->parCategorie($debut, $fin)->keyBy('categorie');
        $categories = CategorieActivite::orderBy('nom')->pluck('nom')
            ->merge($detail->keys())
            ->unique()
            ->map(fn ($nom) => $detail[$nom] ?? ['categorie' => $nom, 'entrees' => 0.0, 'depenses' => 0.0, 'benefice' => 0.0])
            ->values();

        $resultat = $finances->resultat($debut, $fin);
        $repartition = $finances->repartition($resultat['benefice']);
        $acquisitions = $finances->acquisitionsParType($debut, $fin);

        return view('livewire.pilotage.rapport-mensuel', [
            'moisDisponibles' => $finances->mois()->reverse(),
            'libelleMois' => Finances::libelleMois($debut),
            'categories' => $categories,
            'resultat' => $resultat,
            'taux' => $finances->taux(),
            'repartition' => $repartition,
            'acquisitions' => $acquisitions,
            'resteAInvestir' => $repartition['investissement'] - $acquisitions['Acquisition'],
            'resteReserve' => $repartition['epargne'] - $acquisitions['Depense_Reserve'],
        ])->layout('layouts.app', ['title' => 'Rapport mensuel']);
    }
}
