<?php

namespace App\Livewire\Comptabilite;

use App\Models\CategorieActivite;
use App\Models\EcritureJournal;
use App\Support\CompteResolver;
use Livewire\Component;
use Livewire\WithPagination;

class EcrituresJournal extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public ?int $editingId = null;

    // Filtres (equivalent des fleches de filtre du tableau Excel, en plus complet).
    public string $du = '';
    public string $au = '';
    public string $filtreCategorie = '';
    public string $filtreType = '';
    public string $filtreCompte = '';
    public string $recherche = '';

    public function updating(string $name): void
    {
        if (in_array($name, ['du', 'au', 'filtreCategorie', 'filtreType', 'filtreCompte', 'recherche'], true)) {
            $this->resetPage();
        }
    }

    public function filtrerMoisEnCours(): void
    {
        $this->du = now()->startOfMonth()->toDateString();
        $this->au = now()->endOfMonth()->toDateString();
        $this->resetPage();
    }

    public function reinitialiserFiltres(): void
    {
        $this->reset(['du', 'au', 'filtreCategorie', 'filtreType', 'filtreCompte', 'recherche']);
        $this->resetPage();
    }

    /**
     * @return array<string, string>
     */
    public function filtres(): array
    {
        return [
            'du' => $this->du,
            'au' => $this->au,
            'categorie' => $this->filtreCategorie,
            'type' => $this->filtreType,
            'compte' => $this->filtreCompte,
            'recherche' => $this->recherche,
        ];
    }

    public string $date = '';
    public ?int $categorie_activite_id = null;
    public string $type = 'Entree';
    public string $description = '';
    public string $montant = '0';
    public string $moyen_paiement = 'Especes';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier('COMPTABILITE_JOURNAL'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('COMPTABILITE_JOURNAL'), 403);

        $data = $this->validate([
            'date' => 'required|date',
            'categorie_activite_id' => 'nullable|existe_ici:categories_activite',
            'type' => 'required|in:Entree,Depense',
            'description' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0.01',
            'moyen_paiement' => 'required|string|max:50',
        ]);

        $data['categorie_activite_id'] = $data['categorie_activite_id'] ?: null;
        $data['compte'] = CompteResolver::depuisMoyenPaiement($data['moyen_paiement']);
        $data['utilisateur_id'] = auth()->id();

        if ($this->editingId) {
            $this->ecritureModifiable($this->editingId)->update($data);
            $message = 'Ecriture modifiee avec succes.';
        } else {
            EcritureJournal::create($data);
            $message = 'Ecriture de journal enregistree avec succes.';
        }

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', $message);
    }

    public function modifier(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('COMPTABILITE_JOURNAL'), 403);

        $ecriture = $this->ecritureModifiable($id);

        $this->resetForm();
        $this->editingId = $ecriture->id;
        $this->date = $ecriture->date->toDateString();
        $this->categorie_activite_id = $ecriture->categorie_activite_id;
        $this->type = $ecriture->type;
        $this->description = $ecriture->description;
        $this->montant = (string) (float) $ecriture->montant;
        $this->moyen_paiement = $ecriture->moyen_paiement;
        $this->showForm = true;
    }

    /**
     * Les lignes generees par une vente (vente_id) se corrigent depuis le module
     * Ventes, qui les tient a jour : on ne les modifie pas a la main ici, sinon
     * le journal et les ventes divergeraient.
     */
    private function ecritureModifiable(int $id): EcritureJournal
    {
        return EcritureJournal::whereNull('vente_id')->findOrFail($id);
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('COMPTABILITE_JOURNAL'), 403);

        $this->ecritureModifiable($id)->delete();
        session()->flash('status', 'Écriture supprimée.');
    }

    public function annuler(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'categorie_activite_id', 'description', 'montant', 'moyen_paiement']);
        $this->date = now()->toDateString();
        $this->type = 'Entree';
        $this->montant = '0';
        $this->moyen_paiement = 'Especes';
        $this->resetErrorBag();
    }

    public function render()
    {
        $filtrees = EcritureJournal::query()->filtrer($this->filtres());
        $totalEntrees = (float) (clone $filtrees)->where('type', 'Entree')->sum('montant');
        $totalDepenses = (float) (clone $filtrees)->where('type', 'Depense')->sum('montant');

        $ecritures = $filtrees
            ->with(['categorieActivite', 'utilisateur'])
            ->latest('date')
            ->latest('id')
            ->paginate(25);

        return view('livewire.comptabilite.ecritures-journal', [
            'ecritures' => $ecritures,
            'totalEntrees' => $totalEntrees,
            'totalDepenses' => $totalDepenses,
            'filtresActifs' => array_filter($this->filtres()) !== [],
            'lienExport' => route('comptabilite.journal.exporter', array_filter($this->filtres())),
            'categoriesDisponibles' => CategorieActivite::where('actif', true)->orderBy('nom')->get(),
        ])->layout('layouts.app', ['title' => 'Journal des transactions']);
    }
}
