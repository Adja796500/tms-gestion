<?php

namespace App\Livewire\Concerns;

use App\Models\Client;
use App\Models\Facture;
use App\Models\FactureLigne;
use App\Models\Fournisseur;
use App\Support\EnvoiDocument;
use App\Support\NumeroGenerator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

/**
 * Feuilles BORDEREAU_LIVRAISON et BON_COMMANDE du classeur. Les deux documents
 * partagent la table factures (types bordereau / bon_commande) :
 * - le bordereau s'adresse a un client, n'a pas de prix, chaque ligne peut avoir
 *   une observation et il peut citer la facture qu'il livre ;
 * - le bon de commande s'adresse a un fournisseur, avec prix et remise (%).
 *
 * La classe qui utilise ce trait declare $type (Facture::TYPE_BORDEREAU ou
 * Facture::TYPE_BON_COMMANDE).
 */
trait GereDocumentsCommerciaux
{
    public bool $showForm = false;
    public ?int $editingId = null;

    public string $search = '';

    public ?int $partenaire_id = null;
    public string $date = '';
    public string $statut = 'En attente';
    public string $objet = '';
    public ?int $facture_liee_id = null;
    public string $remise_pourcentage = '0';

    /** Arrivee depuis une facture ("-> Bordereau") : ouvre le formulaire prerempli. */
    #[Url(as: 'facture')]
    public ?int $depuisFacture = null;

    /** @var array<int, array{designation: string, quantite: int|string, prix_unitaire: int|string, observations: string}> */
    public array $lignes = [];

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->lignes = [$this->ligneVide()];

        if ($this->depuisFacture && ! $this->estBonCommande() && auth()->user()->peutModifier($this->moduleCode())) {
            $this->showForm = true;
            $this->facture_liee_id = $this->depuisFacture;
            $this->updatedFactureLieeId($this->depuisFacture);
            $this->depuisFacture = null;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    private function estBonCommande(): bool
    {
        return $this->type === Facture::TYPE_BON_COMMANDE;
    }

    private function moduleCode(): string
    {
        return $this->estBonCommande() ? 'BON_COMMANDE' : 'BORDEREAU_LIVRAISON';
    }

    private function ligneVide(): array
    {
        return ['designation' => '', 'quantite' => 1, 'prix_unitaire' => 0, 'observations' => ''];
    }

    public function ajouterLigne(): void
    {
        $this->lignes[] = $this->ligneVide();
    }

    public function retirerLigne(int $index): void
    {
        unset($this->lignes[$index]);
        $this->lignes = array_values($this->lignes);

        if ($this->lignes === []) {
            $this->lignes = [$this->ligneVide()];
        }
    }

    /**
     * Le bordereau peut reprendre les lignes de la facture qu'il livre, comme le
     * report manuel facture -> bordereau du classeur, sans ressaisie.
     */
    public function updatedFactureLieeId($value): void
    {
        $facture = $value ? Facture::where('type', Facture::TYPE_DEFINITIVE)->with('lignes')->find($value) : null;

        if (! $facture) {
            return;
        }

        $this->partenaire_id = $facture->client_id;
        $this->objet = $this->objet ?: (string) $facture->objet;
        $this->lignes = $facture->lignes->map(fn (FactureLigne $l) => [
            'designation' => $l->designation,
            'quantite' => $l->quantite,
            'prix_unitaire' => 0,
            'observations' => '',
        ])->all() ?: [$this->ligneVide()];
    }

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier($this->moduleCode()), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function modifier(int $id): void
    {
        abort_unless(auth()->user()->peutModifier($this->moduleCode()), 403);

        $document = Facture::where('type', $this->type)->with('lignes')->findOrFail($id);

        $this->resetForm();
        $this->editingId = $document->id;
        $this->partenaire_id = $this->estBonCommande() ? $document->fournisseur_id : $document->client_id;
        $this->date = $document->date->toDateString();
        $this->statut = $document->statut;
        $this->objet = (string) $document->objet;
        $this->facture_liee_id = $document->facture_liee_id;
        $this->remise_pourcentage = (string) (float) $document->remise_pourcentage;
        $this->lignes = $document->lignes->map(fn (FactureLigne $l) => [
            'designation' => $l->designation,
            'quantite' => $l->quantite,
            'prix_unitaire' => (string) (float) $l->prix_unitaire,
            'observations' => (string) $l->observations,
        ])->all() ?: [$this->ligneVide()];
        $this->showForm = true;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier($this->moduleCode()), 403);

        $this->validate([
            'partenaire_id' => 'required|existe_ici:'.($this->estBonCommande() ? 'fournisseurs' : 'clients'),
            'date' => 'required|date',
            'statut' => 'required|in:'.implode(',', Facture::STATUTS),
            'objet' => 'nullable|string|max:255',
            'facture_liee_id' => 'nullable|existe_ici:factures',
            'remise_pourcentage' => 'nullable|numeric|min:0|max:100',
            'lignes' => 'required|array|min:1',
            'lignes.*.designation' => 'required|string|max:255',
            'lignes.*.quantite' => 'required|integer|min:1',
            'lignes.*.prix_unitaire' => 'nullable|numeric|min:0',
            'lignes.*.observations' => 'nullable|string|max:255',
        ], [
            'partenaire_id.required' => $this->estBonCommande() ? 'Veuillez choisir le fournisseur.' : 'Veuillez choisir le client.',
            'lignes.*.designation.required' => 'Désignation obligatoire.',
        ]);

        $avecPrix = $this->estBonCommande();
        $sousTotal = 0;
        foreach ($this->lignes as $ligne) {
            $sousTotal += $avecPrix ? (int) $ligne['quantite'] * (float) ($ligne['prix_unitaire'] ?: 0) : 0;
        }
        $remise = $avecPrix ? (float) ($this->remise_pourcentage ?: 0) : 0;

        $attributs = [
            'client_id' => $this->estBonCommande() ? null : $this->partenaire_id,
            'fournisseur_id' => $this->estBonCommande() ? $this->partenaire_id : null,
            'facture_liee_id' => $this->estBonCommande() ? null : ($this->facture_liee_id ?: null),
            'objet' => $this->objet ?: null,
            'date' => $this->date,
            'statut' => $this->statut,
            'remise_pourcentage' => $remise,
            'montant_total' => round($sousTotal * (1 - $remise / 100), 2),
        ];

        $document = DB::transaction(function () use ($attributs, $avecPrix) {
            if ($this->editingId) {
                $document = Facture::where('type', $this->type)->findOrFail($this->editingId);
                $document->update($attributs);
                $document->lignes()->delete();
            } else {
                $document = Facture::create($attributs + [
                    'type' => $this->type,
                    'numero' => NumeroGenerator::suivant(
                        'factures_'.$this->type,
                        $this->estBonCommande() ? 'BC' : 'BL',
                        auth()->user()->entreprise_id
                    ),
                ]);
            }

            foreach ($this->lignes as $ligne) {
                $prix = $avecPrix ? (float) ($ligne['prix_unitaire'] ?: 0) : 0;

                FactureLigne::create([
                    'facture_id' => $document->id,
                    'designation' => $ligne['designation'],
                    'quantite' => (int) $ligne['quantite'],
                    'prix_unitaire' => $prix,
                    'total' => (int) $ligne['quantite'] * $prix,
                    'observations' => ($ligne['observations'] ?? '') ?: null,
                ]);
            }

            return $document;
        });

        $message = $this->editingId ? 'Document modifié avec succès.' : trim('Document créé avec succès. '.EnvoiDocument::apresCreation($document));
        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', $message);
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier($this->moduleCode()), 403);

        Facture::where('type', $this->type)->findOrFail($id)->delete();
        session()->flash('status', 'Document supprimé.');
    }

    public function annuler(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'partenaire_id', 'objet', 'facture_liee_id', 'remise_pourcentage']);
        $this->date = now()->toDateString();
        $this->statut = 'En attente';
        $this->remise_pourcentage = '0';
        $this->lignes = [$this->ligneVide()];
        $this->resetErrorBag();
    }

    public function render()
    {
        $documents = Facture::query()
            ->where('type', $this->type)
            ->with(['client', 'fournisseur', 'lignes', 'factureLiee'])
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('numero', 'ilike', "%{$this->search}%")
                        ->orWhereHas('client', fn ($c) => $c->where('nom', 'ilike', "%{$this->search}%"))
                        ->orWhereHas('fournisseur', fn ($f) => $f->where('nom', 'ilike', "%{$this->search}%"));
                });
            })
            ->latest('date')
            ->latest('id')
            ->paginate(10);

        $titre = $this->estBonCommande() ? 'Bons de commande' : 'Bordereaux de livraison';

        return view('livewire.documents-commerciaux', [
            'documents' => $documents,
            'titre' => $titre,
            'moduleCode' => $this->moduleCode(),
            'estBonCommande' => $this->estBonCommande(),
            'partenaires' => $this->estBonCommande() ? Fournisseur::orderBy('nom')->get() : Client::orderBy('nom')->get(),
            'facturesDisponibles' => $this->estBonCommande() ? collect() : Facture::where('type', Facture::TYPE_DEFINITIVE)->with('client')->latest('date')->limit(100)->get(),
        ])->layout('layouts.app', ['title' => $titre]);
    }
}
