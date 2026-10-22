<?php

namespace App\Livewire;

use App\Models\Achat;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Support\NumeroGenerator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Achats extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showForm = false;

    public string $date = '';
    public ?int $article_id = null;
    public ?int $fournisseur_id = null;
    public string $quantite = '1';
    public string $prix_unitaire = '0';
    public string $mode_reglement = 'Comptant';
    public string $montant_regle = '0';

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
        abort_unless(auth()->user()->peutModifier('ACHATS_STOCK'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('ACHATS_STOCK'), 403);

        $data = $this->validate([
            'date' => 'required|date',
            'article_id' => 'required|existe_ici:articles',
            'fournisseur_id' => 'nullable|existe_ici:fournisseurs',
            'quantite' => 'required|integer|min:1',
            'prix_unitaire' => 'required|numeric|min:0',
            'mode_reglement' => 'required|in:Comptant,Credit',
            'montant_regle' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($data) {
            $total = $data['quantite'] * $data['prix_unitaire'];
            $montantRegle = $data['mode_reglement'] === 'Comptant'
                ? $total
                : (float) ($data['montant_regle'] ?? 0);

            $achat = Achat::create([
                'numero' => NumeroGenerator::suivant('achats', 'ACH', auth()->user()->entreprise_id),
                'date' => $data['date'],
                'article_id' => $data['article_id'],
                'fournisseur_id' => $data['fournisseur_id'] ?: null,
                'quantite' => $data['quantite'],
                'prix_unitaire' => $data['prix_unitaire'],
                'total' => $total,
                'mode_reglement' => $data['mode_reglement'],
                'montant_regle' => $montantRegle,
            ]);

            $achat->rafraichirStatut();

            Article::findOrFail($data['article_id'])->recalculerStock();
        });

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', 'Achat enregistré avec succès.');
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('ACHATS_STOCK'), 403);

        DB::transaction(function () use ($id) {
            $achat = Achat::findOrFail($id);
            $articleId = $achat->article_id;
            $achat->delete();
            Article::findOrFail($articleId)->recalculerStock();
        });

        session()->flash('status', 'Achat supprimé.');
    }

    public function annuler(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['article_id', 'fournisseur_id', 'quantite', 'prix_unitaire', 'mode_reglement', 'montant_regle']);
        $this->date = now()->toDateString();
        $this->quantite = '1';
        $this->prix_unitaire = '0';
        $this->mode_reglement = 'Comptant';
        $this->montant_regle = '0';
        $this->resetErrorBag();
    }

    public function render()
    {
        $achats = Achat::query()
            ->with(['article', 'fournisseur'])
            ->when($this->search, function ($query) {
                $query->where('numero', 'ilike', "%{$this->search}%")
                    ->orWhereHas('article', fn ($q) => $q->where('designation', 'ilike', "%{$this->search}%"));
            })
            ->latest('date')
            ->latest('id')
            ->paginate(10);

        return view('livewire.achats', [
            'achats' => $achats,
            'articlesDisponibles' => Article::orderBy('designation')->get(),
            'fournisseursDisponibles' => Fournisseur::orderBy('nom')->get(),
        ])->layout('layouts.app', ['title' => 'Achats de stock']);
    }
}
