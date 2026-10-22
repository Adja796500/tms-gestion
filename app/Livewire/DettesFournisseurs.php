<?php

namespace App\Livewire;

use App\Models\Fournisseur;
use Livewire\Component;
use Livewire\WithPagination;

class DettesFournisseurs extends Component
{
    use WithPagination;

    public function render()
    {
        // L'argent verse a l'achat et les reglements rattaches a un achat sont dans
        // achats.montant_regle ; les reglements libres (sans achat) s'y ajoutent.
        $fournisseurs = Fournisseur::withSum('achats as total_achete', 'total')
            ->withSum('achats as regle_achats', 'montant_regle')
            ->withSum(['reglements as regle_libre' => fn ($q) => $q->whereNull('achat_id')], 'montant')
            ->orderBy('nom')
            ->paginate(15);

        $fournisseurs->getCollection()->transform(function (Fournisseur $fournisseur) {
            $fournisseur->total_regle = (float) ($fournisseur->regle_achats ?? 0) + (float) ($fournisseur->regle_libre ?? 0);
            $fournisseur->solde = round((float) ($fournisseur->total_achete ?? 0) - $fournisseur->total_regle, 2);

            return $fournisseur;
        });

        return view('livewire.dettes-fournisseurs', ['fournisseurs' => $fournisseurs])
            ->layout('layouts.app', ['title' => 'Dettes fournisseurs']);
    }
}
