<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Facture;
use App\Models\PaiementClient;
use App\Models\Vente;
use App\Support\NumeroGenerator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PaiementsClients extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public string $date = '';
    public ?int $client_id = null;
    public ?int $vente_id = null;
    public ?int $facture_id = null;
    public string $montant = '0';
    public string $mode_paiement = 'Especes';
    public string $observations = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier('PAIEMENTS_CLIENTS'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('PAIEMENTS_CLIENTS'), 403);

        $data = $this->validate([
            'date' => 'required|date',
            'client_id' => 'required|existe_ici:clients',
            'vente_id' => 'nullable|existe_ici:ventes',
            'facture_id' => 'nullable|existe_ici:factures',
            'montant' => 'required|numeric|min:0.01',
            'mode_paiement' => 'required|string|max:50',
            'observations' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data) {
            $data['numero'] = NumeroGenerator::suivant('paiements_clients', 'PAI', auth()->user()->entreprise_id);
            $data['vente_id'] = $data['vente_id'] ?: null;
            $data['facture_id'] = $data['facture_id'] ?: null;

            PaiementClient::create($data);
        });

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', 'Paiement client enregistré avec succès.');
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('PAIEMENTS_CLIENTS'), 403);

        PaiementClient::findOrFail($id)->delete();
        session()->flash('status', 'Paiement supprimé.');
    }

    public function annuler(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['client_id', 'vente_id', 'facture_id', 'montant', 'mode_paiement', 'observations']);
        $this->date = now()->toDateString();
        $this->montant = '0';
        $this->mode_paiement = 'Especes';
        $this->resetErrorBag();
    }

    public function render()
    {
        $paiements = PaiementClient::query()
            ->with(['client', 'vente'])
            ->latest('date')
            ->latest('id')
            ->paginate(10);

        return view('livewire.paiements-clients', [
            'paiements' => $paiements,
            'clientsDisponibles' => Client::orderBy('nom')->get(),
            'ventesDisponibles' => Vente::orderBy('numero')->get(),
            // Facture reglee par ce paiement : alimente "Montant paye" dans le Registre des documents.
            'facturesDisponibles' => Facture::where('type', Facture::TYPE_DEFINITIVE)
                ->when($this->client_id, fn ($q) => $q->where('client_id', $this->client_id))
                ->latest('date')
                ->get(),
        ])->layout('layouts.app', ['title' => 'Paiements clients']);
    }
}
