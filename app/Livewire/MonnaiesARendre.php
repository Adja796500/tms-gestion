<?php

namespace App\Livewire;

use App\Models\MonnaieARendre;
use Livewire\Component;
use Livewire\WithPagination;

class MonnaiesARendre extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function marquerReglee(int $id): void
    {
        abort_unless(auth()->user()->peutModifier(Ventes::MODULES_CAISSE), 403);

        $entree = MonnaieARendre::findOrFail($id);
        $entree->update(['statut' => 'reglee', 'reglee_le' => now()]);

        session()->flash('status', 'Monnaie marquée comme rendue.');

        // Preuve immediate que la monnaie a bien ete rendue, pour eviter toute
        // contestation ulterieure du client faute de justificatif.
        $this->dispatch('monnaie-reglee', monnaieId: $entree->id);
    }

    public function render()
    {
        $entrees = MonnaieARendre::query()
            ->with(['client', 'vente'])
            ->when($this->search, fn ($q) => $q->whereHas('client', fn ($c) => $c->where('nom', 'ilike', "%{$this->search}%")))
            ->orderByRaw("statut = 'due' desc")
            ->latest('date')
            ->latest('id')
            ->paginate(15);

        $totalDu = MonnaieARendre::where('statut', 'due')->sum('montant');

        return view('livewire.monnaies-a-rendre', [
            'entrees' => $entrees,
            'totalDu' => $totalDu,
        ])->layout('layouts.app', ['title' => 'Monnaie à rendre']);
    }
}
