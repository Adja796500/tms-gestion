<?php

namespace App\Livewire;

use App\Models\CategorieActivite;
use Livewire\Component;
use Livewire\WithPagination;

class CategoriesActivite extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;
    public bool $showForm = false;

    public string $nom = '';
    public bool $actif = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier('CATEGORIES_ACTIVITE'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function modifier(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('CATEGORIES_ACTIVITE'), 403);

        $categorie = CategorieActivite::findOrFail($id);
        $this->editingId = $categorie->id;
        $this->nom = $categorie->nom;
        $this->actif = $categorie->actif;
        $this->showForm = true;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('CATEGORIES_ACTIVITE'), 403);

        $data = $this->validate([
            'nom' => 'required|string|max:255',
            'actif' => 'boolean',
        ]);

        $categorie = CategorieActivite::updateOrCreate(['id' => $this->editingId], $data);

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', "Catégorie {$categorie->nom} enregistrée avec succès.");
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('CATEGORIES_ACTIVITE'), 403);

        CategorieActivite::findOrFail($id)->delete();
        session()->flash('status', 'Catégorie supprimée.');
    }

    public function annuler(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'nom']);
        $this->actif = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $categories = CategorieActivite::query()
            ->when($this->search, fn ($q) => $q->where('nom', 'ilike', "%{$this->search}%"))
            ->withCount('articles')
            ->orderBy('nom')
            ->paginate(10);

        return view('livewire.categories-activite', ['categories' => $categories])
            ->layout('layouts.app', ['title' => "Catégories d'activité"]);
    }
}
