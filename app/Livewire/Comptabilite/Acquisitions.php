<?php

namespace App\Livewire\Comptabilite;

use App\Models\Acquisition;
use App\Models\Fournisseur;
use App\Support\CompteResolver;
use Livewire\Component;
use Livewire\WithPagination;

class Acquisitions extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public string $date = '';
    public string $type = 'Acquisition';
    public string $designation = '';
    public string $montant = '0';
    public ?int $fournisseur_id = null;
    public string $moyen_paiement = 'Especes';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier('COMPTABILITE_ACQUISITIONS'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('COMPTABILITE_ACQUISITIONS'), 403);

        $data = $this->validate([
            'date' => 'required|date',
            'type' => 'required|in:Acquisition,Pret_Benefices,Depense_Reserve',
            'designation' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0.01',
            'fournisseur_id' => 'nullable|existe_ici:fournisseurs',
            'moyen_paiement' => 'required|string|max:50',
        ]);

        $data['fournisseur_id'] = $data['fournisseur_id'] ?: null;
        $data['compte'] = CompteResolver::depuisMoyenPaiement($data['moyen_paiement']);
        $data['utilisateur_id'] = auth()->id();

        Acquisition::create($data);

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', 'Acquisition enregistrée avec succès.');
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('COMPTABILITE_ACQUISITIONS'), 403);

        Acquisition::findOrFail($id)->delete();
        session()->flash('status', 'Acquisition supprimée.');
    }

    public function annuler(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['fournisseur_id', 'designation', 'montant', 'moyen_paiement']);
        $this->date = now()->toDateString();
        $this->type = 'Acquisition';
        $this->montant = '0';
        $this->moyen_paiement = 'Especes';
        $this->resetErrorBag();
    }

    public function render()
    {
        $acquisitions = Acquisition::query()
            ->with(['fournisseur', 'utilisateur'])
            ->latest('date')
            ->latest('id')
            ->paginate(10);

        return view('livewire.comptabilite.acquisitions', [
            'acquisitions' => $acquisitions,
            'fournisseursDisponibles' => Fournisseur::orderBy('nom')->get(),
        ])->layout('layouts.app', ['title' => 'Acquisitions']);
    }
}
