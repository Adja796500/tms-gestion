<?php

namespace App\Livewire;

use App\Models\Fournisseur;
use Livewire\Component;
use Livewire\WithPagination;

class Fournisseurs extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;
    public bool $showForm = false;

    public string $nom = '';
    public string $telephone = '';
    public string $email = '';
    public string $adresse = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier('FOURNISSEURS'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function modifier(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('FOURNISSEURS'), 403);

        $fournisseur = Fournisseur::findOrFail($id);
        $this->editingId = $fournisseur->id;
        $this->nom = $fournisseur->nom;
        $this->telephone = (string) $fournisseur->telephone;
        $this->email = (string) $fournisseur->email;
        $this->adresse = (string) $fournisseur->adresse;
        $this->showForm = true;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('FOURNISSEURS'), 403);

        $data = $this->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:255',
        ]);

        $fournisseur = Fournisseur::updateOrCreate(['id' => $this->editingId], $data);

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', "Fournisseur {$fournisseur->nom} enregistré avec succès.");
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('FOURNISSEURS'), 403);

        Fournisseur::findOrFail($id)->delete();
        session()->flash('status', 'Fournisseur supprimé.');
    }

    public function annuler(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'nom', 'telephone', 'email', 'adresse']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $fournisseurs = Fournisseur::query()
            ->when($this->search, fn ($q) => $q->where('nom', 'ilike', "%{$this->search}%"))
            ->withCount('achats')
            ->orderBy('nom')
            ->paginate(10);

        return view('livewire.fournisseurs', ['fournisseurs' => $fournisseurs])
            ->layout('layouts.app', ['title' => 'Fournisseurs']);
    }
}
