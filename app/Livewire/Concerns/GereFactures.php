<?php

namespace App\Livewire\Concerns;

use App\Models\Client;
use App\Models\Facture;
use App\Models\FactureLigne;
use App\Models\ParametreEntreprise;
use App\Models\Vente;
use App\Models\VenteLigne;
use App\Support\EnvoiDocument;
use App\Support\NumeroGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait GereFactures
{
    public bool $showForm = false;

    public ?int $client_id = null;

    public string $clientRecherche = '';

    public string $date = '';

    public string $objet = '';

    /** @var array<int, array{designation: string, quantite: int|string, prix_unitaire: int|string}> */
    public array $lignes = [];

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->lignes = [$this->ligneVide()];
    }

    private function ligneVide(): array
    {
        return ['designation' => '', 'quantite' => 1, 'prix_unitaire' => 0];
    }

    public function ajouterLigne(): void
    {
        $this->lignes[] = $this->ligneVide();
    }

    public function retirerLigne(int $index): void
    {
        unset($this->lignes[$index]);
        $this->lignes = array_values($this->lignes);
    }

    public function updated(string $name, $value): void
    {
        if ($name === 'clientRecherche') {
            $client = $this->resoudreClient((string) $value);
            $this->client_id = $client?->id;

            if ($client) {
                $this->preRemplirLignesDepuisAchats($client);
            }
        }
    }

    /**
     * Une saisie partielle qui ne cible plus qu'un seul client est acceptee
     * directement, comme dans le panier de Ventes.
     */
    private function resoudreClient(string $texte): ?Client
    {
        $matches = $this->clientsCorrespondants($texte);

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * @return Collection<int, Client>
     */
    public function clientsCorrespondants(?string $texte = null): Collection
    {
        $texte = trim($texte ?? $this->clientRecherche);

        if ($texte === '') {
            return collect();
        }

        return Client::where('nom', 'ilike', "%{$texte}%")->limit(6)->get();
    }

    /**
     * Alimente le menu deroulant personnalise du client : correspondances a la
     * saisie, ou les premiers clients si le champ est encore vide.
     *
     * @return Collection<int, Client>
     */
    public function clientsPourListe(?string $texte = null): Collection
    {
        $texte = trim($texte ?? $this->clientRecherche);

        return Client::query()
            ->when($texte !== '', fn ($q) => $q->where('nom', 'ilike', "%{$texte}%"))
            ->orderBy('nom')
            ->limit(20)
            ->get();
    }

    public function selectionnerClient(int $id): void
    {
        abort_unless(auth()->user()->peutModifier($this->moduleCode()), 403);

        $client = Client::find($id);

        if ($client) {
            $this->client_id = $client->id;
            $this->clientRecherche = $client->nom;
            $this->preRemplirLignesDepuisAchats($client);
        }
    }

    /**
     * Pre-remplit les lignes du document avec les articles des dernieres ventes du
     * client : "ce qu'il a paye" au niveau article n'existe pas en base (le paiement
     * est suivi au niveau Vente, pas ligne par ligne), donc on reprend ses derniers
     * achats comme point de depart modifiable plutot que de partir d'une ligne vide.
     * On ne remplace les lignes que si le document est encore vierge (une seule
     * ligne sans designation), pour ne jamais ecraser une saisie deja commencee.
     */
    private function preRemplirLignesDepuisAchats(Client $client): void
    {
        if (count($this->lignes) !== 1 || trim((string) $this->lignes[0]['designation']) !== '') {
            return;
        }

        $lignes = Vente::where('client_id', $client->id)
            ->with('lignes.article')
            ->latest('date')
            ->latest('id')
            ->limit(5)
            ->get()
            ->flatMap(fn (Vente $v) => $v->lignes)
            ->filter(fn (VenteLigne $l) => $l->article !== null)
            ->unique('article_id')
            ->take(5)
            ->map(fn (VenteLigne $l) => [
                'designation' => $l->article->designation,
                'quantite' => $l->quantite,
                'prix_unitaire' => (string) $l->prix_unitaire,
            ])
            ->values()
            ->all();

        if ($lignes !== []) {
            $this->lignes = $lignes;
        }
    }

    /**
     * Fiche client affichee sous le champ Client une fois selectionne : coordonnees
     * et solde (meme calcul que le module Creances clients, base sur ses Ventes et
     * Paiements), pour aider a la saisie sans avoir a changer de page. Purement
     * informatif : n'alimente aucun champ du formulaire ni le document imprime — les
     * articles achetes, eux, pre-remplissent directement les lignes du document (voir
     * preRemplirLignesDepuisAchats()).
     *
     * @return array{client: Client, totalPaye: float, resteAPayer: float, dernieresFactures: Collection<int, Facture>}|null
     */
    public function clientInfos(): ?array
    {
        if (! $this->client_id) {
            return null;
        }

        $client = Client::find($this->client_id);

        if (! $client) {
            return null;
        }

        return [
            'client' => $client,
            'totalPaye' => $client->totalPaye(),
            'resteAPayer' => $client->resteAPayer(),
            'dernieresFactures' => Facture::where('client_id', $client->id)
                ->latest('date')
                ->latest('id')
                ->limit(5)
                ->get(),
        ];
    }

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier($this->moduleCode()), 403);

        $this->reset(['client_id', 'clientRecherche', 'lignes', 'objet']);
        $this->date = now()->toDateString();
        $this->lignes = [$this->ligneVide()];
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function annuler(): void
    {
        $this->showForm = false;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier($this->moduleCode()), 403);

        // Un nom de client saisi qui ne correspond a rien d'existant est enregistre
        // automatiquement ici, avant validation (meme logique que le panier de Ventes) :
        // une seule action (Enregistrer) suffit, le client est retrouvable ensuite.
        if (! $this->client_id && trim($this->clientRecherche) !== '') {
            if ($this->clientsCorrespondants()->count() > 1) {
                $this->addError('client_id', 'Plusieurs clients correspondent à cette saisie : précisez le nom.');
            } else {
                abort_unless(auth()->user()->peutModifier('CLIENTS'), 403);
                $client = Client::create(['nom' => trim($this->clientRecherche)]);
                $this->client_id = $client->id;
                $this->clientRecherche = $client->nom;
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->validate([
            'date' => 'required|date',
            'client_id' => 'required|existe_ici:clients',
            'objet' => 'nullable|string|max:255',
            'lignes' => 'required|array|min:1',
            'lignes.*.designation' => 'required|string|max:255',
            'lignes.*.quantite' => 'required|integer|min:1',
            'lignes.*.prix_unitaire' => 'required|numeric|min:0',
        ]);

        $facture = DB::transaction(function () {
            $montantTotal = 0;
            foreach ($this->lignes as $ligne) {
                $montantTotal += $ligne['quantite'] * $ligne['prix_unitaire'];
            }

            $facture = Facture::create([
                'type' => $this->type,
                'numero' => $this->numeroSuivant($this->type),
                'client_id' => $this->client_id,
                'objet' => $this->objet ?: null,
                'date' => $this->date,
                'montant_total' => $montantTotal,
                'statut' => 'Emise',
            ]);

            foreach ($this->lignes as $ligne) {
                FactureLigne::create([
                    'facture_id' => $facture->id,
                    'designation' => $ligne['designation'],
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                    'total' => $ligne['quantite'] * $ligne['prix_unitaire'],
                ]);
            }

            return $facture;
        });

        $this->showForm = false;
        session()->flash('status', trim('Document genere avec succes. '.EnvoiDocument::apresCreation($facture)));
    }

    /**
     * Le modele papier de l'entreprise utilise NNN/SIGLE/ANNEE (ex. 018/TMS/2026) : le
     * sigle vient de la configuration entreprise, avec repli sur PRO/FAC. Proforma et
     * definitive gardent deux compteurs independants ; la proforma porte le prefixe "P-".
     */
    private function numeroSuivant(string $type): string
    {
        $prefixe = $type === Facture::TYPE_PROFORMA ? 'PRO' : 'FAC';
        $sigle = ParametreEntreprise::where('entreprise_id', auth()->user()->entreprise_id)->value('sigle') ?: $prefixe;

        // Le prefixe "P-" distingue les proformas des factures : sans lui, la facture
        // 008/TMS/2026 entrerait en collision avec la proforma 008/TMS/2026.
        return ($type === Facture::TYPE_PROFORMA ? 'P-' : '')
            .NumeroGenerator::suivantAvecSigle('factures_'.$type, $sigle, auth()->user()->entreprise_id);
    }

    /**
     * Devis accepte -> facture definitive en un clic : memes client, objet et lignes,
     * nouveau numero de facture ; la proforma passe en "Acceptée". Dans le classeur,
     * il fallait tout ressaisir sur la feuille FACTURE_DEFINITIVE.
     */
    public function convertirEnFacture(int $id): void
    {
        abort_unless($this->type === Facture::TYPE_PROFORMA, 404);
        abort_unless(auth()->user()->peutModifier('FACTURE_DEFINITIVE'), 403);

        $proforma = Facture::where('type', Facture::TYPE_PROFORMA)->with('lignes')->findOrFail($id);

        $facture = DB::transaction(function () use ($proforma) {
            $facture = Facture::create([
                'type' => Facture::TYPE_DEFINITIVE,
                'numero' => $this->numeroSuivant(Facture::TYPE_DEFINITIVE),
                'client_id' => $proforma->client_id,
                'objet' => $proforma->objet,
                'date' => now()->toDateString(),
                'montant_total' => $proforma->montant_total,
                'statut' => 'Emise',
            ]);

            foreach ($proforma->lignes as $ligne) {
                $facture->lignes()->create($ligne->only(['designation', 'quantite', 'prix_unitaire', 'total']));
            }

            $proforma->update(['statut' => 'Acceptée']);

            return $facture;
        });

        session()->flash('status', trim("Proforma {$proforma->numero} convertie en facture {$facture->numero}. ".EnvoiDocument::apresCreation($facture)));
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier($this->moduleCode()), 403);

        Facture::where('type', $this->type)->findOrFail($id)->delete();
        session()->flash('status', 'Document supprimé.');
    }

    private function moduleCode(): string
    {
        return $this->type === Facture::TYPE_PROFORMA ? 'FACTURE_PROFORMA' : 'FACTURE_DEFINITIVE';
    }

    public function render()
    {
        $factures = Facture::query()
            ->where('type', $this->type)
            ->with('client')
            ->latest('date')
            ->latest('id')
            ->paginate(10);

        return view('livewire.gestion-factures', [
            'factures' => $factures,
            'titrePage' => $this->type === Facture::TYPE_PROFORMA ? 'Factures proforma' : 'Factures definitives',
            'moduleCode' => $this->moduleCode(),
        ])->layout('layouts.app', ['title' => $this->type === Facture::TYPE_PROFORMA ? 'Factures proforma' : 'Factures definitives']);
    }
}
