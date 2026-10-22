<?php

namespace App\Livewire;

use App\Models\PaiementClient;
use App\Models\Recu;
use App\Support\NumeroGenerator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Recus extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $paiement_client_id = null;

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier('RECU'), 403);

        $this->reset(['paiement_client_id']);
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function annuler(): void
    {
        $this->showForm = false;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('RECU'), 403);

        $data = $this->validate([
            'paiement_client_id' => 'required|existe_ici:paiements_clients|unique:recus,paiement_client_id',
        ]);

        DB::transaction(function () use ($data) {
            $paiement = PaiementClient::findOrFail($data['paiement_client_id']);

            Recu::create([
                'numero' => NumeroGenerator::suivant('recus', 'REC', auth()->user()->entreprise_id),
                'paiement_client_id' => $paiement->id,
                'date' => $paiement->date,
                'montant' => $paiement->montant,
            ]);
        });

        $this->showForm = false;
        session()->flash('status', 'Reçu généré avec succès.');
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('RECU'), 403);

        Recu::findOrFail($id)->delete();
        session()->flash('status', 'Reçu supprimé.');
    }

    public function render()
    {
        $recus = Recu::query()
            ->with('paiementClient.client')
            ->latest('date')
            ->latest('id')
            ->paginate(10);

        $paiementsSansRecu = PaiementClient::whereDoesntHave('recu')->orderBy('date')->get();

        return view('livewire.recus', [
            'recus' => $recus,
            'paiementsSansRecu' => $paiementsSansRecu,
        ])->layout('layouts.app', ['title' => 'Reçus']);
    }
}
