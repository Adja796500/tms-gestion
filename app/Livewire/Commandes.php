<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Commande;
use App\Support\NumeroGenerator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Commandes extends Component
{
    use WithPagination;

    public const STATUTS = ['En_attente', 'En_cours', 'Executee', 'Livree', 'Annulee'];

    public string $search = '';
    public bool $showForm = false;

    public ?int $client_id = null;
    public string $date = '';
    public string $designation = '';
    public string $quantite = '1';
    public string $montant = '0';
    public string $date_livraison_prevue = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier('COMMANDES'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('COMMANDES'), 403);

        $data = $this->validate([
            'client_id' => 'required|existe_ici:clients',
            'date' => 'required|date',
            'designation' => 'required|string|max:255',
            'quantite' => 'required|integer|min:1',
            'montant' => 'required|numeric|min:0',
            'date_livraison_prevue' => 'nullable|date',
        ]);

        DB::transaction(function () use ($data) {
            Commande::create([
                'client_id' => $data['client_id'],
                'date' => $data['date'],
                'designation' => $data['designation'],
                'quantite' => $data['quantite'],
                'montant' => $data['montant'],
                'date_livraison_prevue' => $data['date_livraison_prevue'] ?: null,
                'numero' => NumeroGenerator::suivant('commandes', 'CMD', auth()->user()->entreprise_id),
                'utilisateur_id' => auth()->id(),
            ]);
        });

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', 'Commande enregistrée avec succès.');
    }

    public function changerStatut(int $id, string $statut): void
    {
        abort_unless(auth()->user()->peutModifier('COMMANDES'), 403);
        abort_unless(in_array($statut, self::STATUTS, true), 422);

        Commande::findOrFail($id)->update(['statut' => $statut]);
        session()->flash('status', 'Statut de la commande mis à jour.');
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('COMMANDES'), 403);

        Commande::findOrFail($id)->delete();
        session()->flash('status', 'Commande supprimée.');
    }

    public function annuler(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['client_id', 'designation', 'quantite', 'montant', 'date_livraison_prevue']);
        $this->date = now()->toDateString();
        $this->quantite = '1';
        $this->montant = '0';
        $this->resetErrorBag();
    }

    public function render()
    {
        $commandes = Commande::query()
            ->with('client')
            ->when($this->search, function ($query) {
                $query->where('numero', 'ilike', "%{$this->search}%")
                    ->orWhereHas('client', fn ($q) => $q->where('nom', 'ilike', "%{$this->search}%"));
            })
            ->latest('date')
            ->latest('id')
            ->paginate(10);

        return view('livewire.commandes', [
            'commandes' => $commandes,
            'clientsDisponibles' => Client::orderBy('nom')->get(),
            'statuts' => self::STATUTS,
        ])->layout('layouts.app', ['title' => 'Commandes']);
    }
}
