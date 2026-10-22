<?php

namespace App\Livewire;

use App\Models\CategorieActivite;
use App\Models\EcritureJournal;
use App\Support\CompteResolver;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Services extends Component
{
    use WithPagination;

    /**
     * Liste de secours du classeur (FmSaisieServices) quand l'entreprise n'a encore
     * declare aucune categorie : la categorie choisie est creee a l'enregistrement.
     */
    private const SERVICES_PAR_DEFAUT = ['ABA', 'Photographie', 'Infographie', 'Caligraphie', 'Sérigraphie', 'Autres'];

    public const MOYENS_PAIEMENT = ['Especes', 'Cheque', 'Virement', 'Mobile Money'];

    public string $filtreService = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $designation = '';
    public string $service = '';
    public string $montantEntree = '';
    public string $montantDepense = '';
    public string $date = '';
    public string $moyen_paiement = 'Especes';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function updatingFiltreService(): void
    {
        $this->resetPage();
    }

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier('SERVICES'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function modifier(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('SERVICES'), 403);

        $ecriture = $this->requeteServices()->with('categorieActivite')->findOrFail($id);

        $this->resetForm();
        $this->editingId = $ecriture->id;
        $this->designation = $ecriture->description;
        $this->service = $ecriture->categorieActivite->nom ?? '';
        $this->date = $ecriture->date->toDateString();
        $this->moyen_paiement = $ecriture->moyen_paiement;

        $montant = (string) (float) $ecriture->montant;
        if ($ecriture->type === 'Depense') {
            $this->montantDepense = $montant;
        } else {
            $this->montantEntree = $montant;
        }

        $this->showForm = true;
    }

    /**
     * Comme dans le classeur : Entree et/ou Depense. Si les deux sont remplis,
     * deux ecritures distinctes sont creees (meme designation, date, service et
     * mode de paiement). En modification, un seul montant a la fois.
     */
    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('SERVICES'), 403);

        $data = $this->validate([
            'designation' => 'required|string|max:255',
            'service' => 'required|string|max:100',
            'montantEntree' => 'nullable|numeric|min:0.01',
            'montantDepense' => 'nullable|numeric|min:0.01',
            'date' => 'required|date',
            'moyen_paiement' => 'required|in:'.implode(',', self::MOYENS_PAIEMENT),
        ], [
            'designation.required' => 'Veuillez saisir une description.',
            'service.required' => 'Veuillez choisir un service.',
        ]);

        $aEntree = $data['montantEntree'] !== null && $data['montantEntree'] !== '';
        $aDepense = $data['montantDepense'] !== null && $data['montantDepense'] !== '';

        if (! $aEntree && ! $aDepense) {
            $this->addError('montantEntree', 'Veuillez saisir un montant, dans Entrée et/ou dans Dépense.');

            return;
        }

        if ($this->editingId && $aEntree && $aDepense) {
            $this->addError('montantEntree', 'En modification, remplissez un seul des deux montants à la fois.');

            return;
        }

        if (mb_strtolower($data['service']) === 'vente') {
            $this->addError('service', 'Les ventes sont enregistrées automatiquement depuis le module Ventes.');

            return;
        }

        $categorie = CategorieActivite::firstOrCreate(['nom' => $data['service']], ['actif' => true]);

        $commun = [
            'date' => $data['date'],
            'categorie_activite_id' => $categorie->id,
            'description' => $data['designation'],
            'moyen_paiement' => $data['moyen_paiement'],
            'compte' => CompteResolver::depuisMoyenPaiement($data['moyen_paiement']),
            'utilisateur_id' => auth()->id(),
        ];

        if ($this->editingId) {
            $this->requeteServices()->findOrFail($this->editingId)->update($commun + [
                'type' => $aEntree ? 'Entree' : 'Depense',
                'montant' => $aEntree ? $data['montantEntree'] : $data['montantDepense'],
            ]);
            $message = 'Écriture modifiée avec succès dans le Journal.';
        } else {
            DB::transaction(function () use ($commun, $aEntree, $aDepense, $data) {
                if ($aDepense) {
                    EcritureJournal::create($commun + ['type' => 'Depense', 'montant' => $data['montantDepense']]);
                }
                if ($aEntree) {
                    EcritureJournal::create($commun + ['type' => 'Entree', 'montant' => $data['montantEntree']]);
                }
            });
            $message = ($aEntree && $aDepense ? '2 écritures enregistrées' : 'Écriture enregistrée').' avec succès dans le Journal.';
        }

        // Le formulaire reste ouvert, pret pour la saisie suivante, comme dans le classeur.
        $this->resetForm();
        $this->showForm = true;
        session()->flash('status', $message);
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('SERVICES'), 403);

        $this->requeteServices()->findOrFail($id)->delete();
        session()->flash('status', 'Écriture supprimée.');
    }

    public function annuler(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    /**
     * Ecritures du journal liees aux services : tout sauf les ventes (qui ont leur
     * propre vue), exactement comme le filtre de FmDonneesServices.
     */
    private function requeteServices()
    {
        return EcritureJournal::query()
            ->whereNull('vente_id')
            ->where(function ($q) {
                $q->whereNull('categorie_activite_id')
                    ->orWhereHas('categorieActivite', fn ($c) => $c->where('nom', '!=', 'Vente'));
            });
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'designation', 'service', 'montantEntree', 'montantDepense']);
        $this->date = now()->toDateString();
        $this->moyen_paiement = 'Especes';
        $this->resetErrorBag();
    }

    public function render()
    {
        $categories = CategorieActivite::where('actif', true)
            ->where('nom', '!=', 'Vente')
            ->orderBy('nom')
            ->pluck('nom');

        $services = $categories->isEmpty() ? collect(self::SERVICES_PAR_DEFAUT) : $categories;

        $requete = $this->requeteServices()
            ->when($this->filtreService !== '', fn ($q) => $q->whereHas('categorieActivite', fn ($c) => $c->where('nom', $this->filtreService)));

        $totalEntrees = (float) (clone $requete)->where('type', 'Entree')->sum('montant');
        $totalDepenses = (float) (clone $requete)->where('type', 'Depense')->sum('montant');

        return view('livewire.services', [
            'ecritures' => $requete->with('categorieActivite')->latest('date')->latest('id')->paginate(15),
            'services' => $services,
            'totalEntrees' => $totalEntrees,
            'totalDepenses' => $totalDepenses,
        ])->layout('layouts.app', ['title' => 'Données des services']);
    }
}
