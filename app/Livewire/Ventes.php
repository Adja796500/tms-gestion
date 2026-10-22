<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Client;
use App\Models\MonnaieARendre;
use App\Models\Vente;
use App\Models\VenteLigne;
use App\Support\JournalVenteSynchroniseur;
use App\Support\NumeroGenerator;
use App\Support\PaiementVenteSynchroniseur;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class Ventes extends Component
{
    public const MODES_PAIEMENT = ['Especes', 'Orange Money', 'Moov Money', 'Cheque', 'Virement'];

    /**
     * Qui tient la caisse : le droit VENTES (magasinier, direction) ou le droit
     * VENTE_RAPIDE (caissier), comme la feuille VENTE_RAPIDE du classeur. La caisse
     * comprend l'historique et la modification d'une vente (le client revient changer
     * ou ajouter un produit) ainsi que la creation rapide d'un article ou d'un client.
     * Seule la suppression d'une vente reste reservee au droit VENTES.
     */
    public const MODULES_CAISSE = 'VENTES|VENTE_RAPIDE';

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $date = '';
    public ?int $client_id = null;
    public string $clientRecherche = '';
    public string $remisePourcentage = '0';
    public string $modePaiement = 'Especes';
    public string $montantRemis = '';
    public bool $monnaieNonRendue = false;

    /** Champ de scan (douchette code-barres, camera ou saisie du code). */
    public string $codeScan = '';

    /** @var array<int, array{article_id: int|string|null, quantite: int|string, prix_unitaire: int|string, recherche: string}> */
    public array $lignes = [];

    /**
     * Permet d'arriver depuis l'Historique des ventes (?modifier=123) et de rouvrir
     * directement cette vente dans le panier, sans repasser par une liste ici.
     */
    #[Url(as: 'modifier')]
    public ?int $modifier_id = null;

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->lignes = [$this->ligneVide()];

        if ($this->modifier_id) {
            $this->modifier($this->modifier_id);

            return;
        }

        $this->showForm = true;
    }

    public function updated(string $name, $value): void
    {
        if ($name === 'clientRecherche') {
            $this->client_id = $this->resoudreClient((string) $value)?->id;

            return;
        }

        if (preg_match('/^lignes\.(\d+)\.recherche$/', $name, $matches)) {
            $index = (int) $matches[1];
            $article = $this->resoudreArticle((string) $value);
            $this->lignes[$index]['article_id'] = $article?->id;

            if ($article) {
                $this->lignes[$index]['prix_unitaire'] = (string) $article->prix_vente;
            }
        }
    }

    /**
     * Une saisie partielle qui ne cible plus qu'un seul client est acceptee
     * directement : pas besoin de taper le nom en entier ni de choisir dans la
     * liste. Si plusieurs clients correspondent encore, on ne devine pas.
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
     * Alimente le menu deroulant personnalise : affiche les clients correspondant a
     * la saisie, ou les premiers clients si le champ est encore vide (pour pouvoir
     * parcourir la liste au clic, meme sans avoir tape quoi que ce soit).
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
        abort_unless(auth()->user()->peutModifier(self::MODULES_CAISSE), 403);

        $client = Client::find($id);

        if ($client) {
            $this->client_id = $client->id;
            $this->clientRecherche = $client->nom;
        }
    }

    /**
     * La liste de suggestions affiche "Designation [CODE]" : on privilegie le code
     * entre crochets (garanti unique) quand l'utilisateur a choisi une suggestion,
     * sinon une saisie partielle qui ne cible plus qu'un seul article est acceptee.
     */
    private function resoudreArticle(string $texte): ?Article
    {
        $texte = trim($texte);

        if (preg_match('/\[([^\]]+)\]\s*$/', $texte, $matches)) {
            $article = Article::where('code', 'ilike', trim($matches[1]))->first();

            if ($article) {
                return $article;
            }
        }

        $matches = $this->articlesCorrespondants($texte);

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * @return Collection<int, Article>
     */
    public function articlesCorrespondants(string $texte): Collection
    {
        $texte = trim($texte);

        if ($texte === '') {
            return collect();
        }

        // La recherche porte a la fois sur le code et la designation : le where
        // englobant evite que le orWhere ne contourne le scope multi-entreprise.
        return Article::where(function ($query) use ($texte) {
            $query->where('code', 'ilike', "%{$texte}%")
                ->orWhere('designation', 'ilike', "%{$texte}%");
        })->limit(6)->get();
    }

    /**
     * Alimente le menu deroulant personnalise des articles : correspondances a la
     * saisie, ou les premiers articles si le champ est encore vide, pour pouvoir
     * parcourir la liste au clic meme apres avoir deja choisi un article.
     *
     * @return Collection<int, Article>
     */
    public function articlesPourListe(?string $texte = null): Collection
    {
        $texte = trim($texte ?? '');

        return Article::query()
            ->when($texte !== '', function ($query) use ($texte) {
                $query->where(function ($q) use ($texte) {
                    $q->where('code', 'ilike', "%{$texte}%")
                        ->orWhere('designation', 'ilike', "%{$texte}%");
                });
            })
            ->orderBy('designation')
            ->limit(20)
            ->get();
    }

    public function selectionnerArticle(int $index, int $id): void
    {
        abort_unless(auth()->user()->peutModifier(self::MODULES_CAISSE), 403);

        $article = Article::find($id);

        if ($article && isset($this->lignes[$index])) {
            $this->lignes[$index]['article_id'] = $article->id;
            $this->lignes[$index]['prix_unitaire'] = (string) $article->prix_vente;
            $this->lignes[$index]['recherche'] = "{$article->designation} [{$article->code}]";
        }
    }

    /**
     * Caisse rapide : un code scanne (code-barres ou code article) ajoute l'article
     * au panier, ou augmente sa quantite s'il y est deja. Une douchette tape le code
     * puis "Entree", ce qui declenche cette methode.
     */
    public function scanner(): void
    {
        abort_unless(auth()->user()->peutModifier(self::MODULES_CAISSE), 403);

        $code = trim($this->codeScan);
        $this->codeScan = '';
        $this->resetErrorBag('codeScan');

        if ($code === '') {
            return;
        }

        $article = Article::where(function ($q) use ($code) {
            $q->where('code_barre', $code)->orWhere('code', 'ilike', $code);
        })->first();

        if (! $article) {
            $this->addError('codeScan', "Aucun article ne correspond au code « {$code} ».");
            $this->dispatch('scan-resultat', ok: false);

            return;
        }

        foreach ($this->lignes as $index => $ligne) {
            if ((int) $ligne['article_id'] === $article->id) {
                $this->lignes[$index]['quantite'] = (int) $ligne['quantite'] + 1;
                $this->dispatch('scan-resultat', ok: true, libelle: $article->designation.' × '.$this->lignes[$index]['quantite']);

                return;
            }
        }

        $nouvelle = [
            'article_id' => $article->id,
            'quantite' => 1,
            'prix_unitaire' => (string) $article->prix_vente,
            'recherche' => "{$article->designation} [{$article->code}]",
        ];

        // Remplit la premiere ligne vide du panier, sinon en ajoute une.
        $vide = collect($this->lignes)->search(fn ($l) => ! $l['article_id'] && trim((string) ($l['recherche'] ?? '')) === '');
        if ($vide !== false) {
            $this->lignes[$vide] = $nouvelle;
        } else {
            $this->lignes[] = $nouvelle;
        }

        $this->dispatch('scan-resultat', ok: true, libelle: $article->designation);
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

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier(self::MODULES_CAISSE), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Reouvre une vente existante dans le panier pour la corriger : un client qui
     * change d'avis sur un article deja paye peut ainsi etre ajuste (quantite,
     * article, remise...) sans passer par une suppression puis une resaisie complete.
     */
    public function modifier(int $id): void
    {
        abort_unless(auth()->user()->peutModifier(self::MODULES_CAISSE), 403);

        $vente = Vente::with(['client', 'lignes.article'])->findOrFail($id);

        $this->editingId = $vente->id;
        $this->date = $vente->date->toDateString();
        $this->client_id = $vente->client_id;
        $this->clientRecherche = $vente->client->nom ?? '';
        $this->remisePourcentage = (string) $vente->remise_pourcentage;
        $this->modePaiement = $vente->mode_paiement ?: 'Especes';
        $this->montantRemis = $vente->montant_remis !== null ? (string) $vente->montant_remis : '';

        $this->lignes = $vente->lignes->map(fn (VenteLigne $ligne) => [
            'article_id' => $ligne->article_id,
            'quantite' => $ligne->quantite,
            'prix_unitaire' => (string) $ligne->prix_unitaire,
            'recherche' => $ligne->article ? "{$ligne->article->designation} [{$ligne->article->code}]" : '',
        ])->all();

        if (empty($this->lignes)) {
            $this->lignes = [$this->ligneVide()];
        }

        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier(self::MODULES_CAISSE), 403);

        // Un nom de client ou d'article saisi qui ne correspond a rien d'existant
        // est enregistre automatiquement ici, avant validation : l'utilisateur n'a
        // qu'une seule action a faire (Enregistrer) et retrouvera ce client/article
        // dans les listes la prochaine fois.
        if (! $this->client_id && trim($this->clientRecherche) !== '') {
            if ($this->clientsCorrespondants()->count() > 1) {
                $this->addError('client_id', 'Plusieurs clients correspondent à cette saisie : précisez le nom.');
            } else {
                // Creation rapide a la caisse (client presse) : autorisee a qui tient la caisse.
                $client = Client::create(['nom' => trim($this->clientRecherche)]);
                $this->client_id = $client->id;
                $this->clientRecherche = $client->nom;
            }
        }

        foreach ($this->lignes as $index => $ligne) {
            if ($ligne['article_id'] || trim($ligne['recherche'] ?? '') === '') {
                continue;
            }

            if ($this->articlesCorrespondants($ligne['recherche'])->count() > 1) {
                $this->addError("lignes.{$index}.article_id", 'Plusieurs articles correspondent à cette saisie : précisez.');

                continue;
            }

            // Creation rapide a la caisse (client presse) : autorisee a qui tient la caisse.
            // Le magasinier pourra completer la fiche (prix d'achat, code-barres) ensuite.

            $designation = trim($ligne['recherche']);
            $quantite = max(1, (int) ($ligne['quantite'] ?: 1));
            $prixVente = (float) ($ligne['prix_unitaire'] ?: 0);

            $article = Article::create([
                'code' => $this->genererCodeArticle($designation),
                'designation' => $designation,
                'prix_vente' => $prixVente,
                'stock_initial' => $quantite,
                'stock_minimum' => 0,
                'stock_disponible' => 0,
            ]);
            $article->recalculerStock();

            $this->lignes[$index]['article_id'] = $article->id;
            $this->lignes[$index]['prix_unitaire'] = (string) $article->prix_vente;
            $this->lignes[$index]['recherche'] = "{$article->designation} [{$article->code}]";
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $data = $this->validate([
            'date' => 'required|date',
            'client_id' => 'required|existe_ici:clients',
            'remisePourcentage' => 'nullable|numeric|min:0|max:100',
            'modePaiement' => 'required|in:'.implode(',', self::MODES_PAIEMENT),
            'montantRemis' => 'nullable|numeric|min:0',
            'lignes' => 'required|array|min:1',
            'lignes.*.article_id' => 'required|existe_ici:articles',
            'lignes.*.quantite' => 'required|integer|min:1',
            'lignes.*.prix_unitaire' => 'required|numeric|min:0',
        ]);

        $sousTotal = 0;
        foreach ($data['lignes'] as $ligne) {
            $sousTotal += $ligne['quantite'] * $ligne['prix_unitaire'];
        }

        $remisePourcentage = (float) ($data['remisePourcentage'] ?: 0);
        $remise = round($sousTotal * $remisePourcentage / 100, 2);
        $total = $sousTotal - $remise;

        $montantRemis = $data['montantRemis'] !== null && $data['montantRemis'] !== ''
            ? (float) $data['montantRemis']
            : null;
        $monnaie = $montantRemis !== null ? round($montantRemis - $total, 2) : 0;

        if ($montantRemis !== null && $montantRemis < $total) {
            $this->addError('montantRemis', 'Le montant remis est insuffisant pour couvrir le total.');

            return;
        }

        // En modification, la vente existante a deja debite le stock : on remet
        // virtuellement ses anciennes quantites en stock avant de comparer aux
        // nouvelles, sinon on se bloquerait nous-meme sur nos propres lignes.
        $ancienneVente = $this->editingId ? Vente::with('lignes')->find($this->editingId) : null;
        $ancienesQuantitesParArticle = [];
        if ($ancienneVente) {
            foreach ($ancienneVente->lignes as $ligne) {
                $ancienesQuantitesParArticle[$ligne->article_id] = ($ancienesQuantitesParArticle[$ligne->article_id] ?? 0) + $ligne->quantite;
            }
        }

        // Un meme article peut apparaitre sur plusieurs lignes du panier : on
        // cumule la quantite demandee par article avant de comparer au stock.
        $quantitesParArticle = [];
        foreach ($data['lignes'] as $ligne) {
            $quantitesParArticle[$ligne['article_id']] = ($quantitesParArticle[$ligne['article_id']] ?? 0) + (int) $ligne['quantite'];
        }

        $articles = Article::whereIn('id', array_keys($quantitesParArticle))->get()->keyBy('id');
        $stockInsuffisant = false;

        foreach ($data['lignes'] as $index => $ligne) {
            $article = $articles->get($ligne['article_id']);

            if (! $article) {
                continue;
            }

            $stockDisponibleAjuste = $article->stock_disponible + ($ancienesQuantitesParArticle[$ligne['article_id']] ?? 0);

            if ($quantitesParArticle[$ligne['article_id']] > $stockDisponibleAjuste) {
                $this->addError("lignes.{$index}.quantite", "Stock insuffisant pour {$article->designation} (disponible : {$stockDisponibleAjuste}).");
                $stockInsuffisant = true;
            }
        }

        if ($stockInsuffisant) {
            return;
        }

        $editingId = $this->editingId;
        $monnaieNonRendue = $this->monnaieNonRendue;

        $vente = DB::transaction(function () use ($data, $total, $remise, $remisePourcentage, $montantRemis, $monnaie, $monnaieNonRendue, $editingId) {
            $attributs = [
                'date' => $data['date'],
                'client_id' => $data['client_id'],
                'total' => $total,
                'remise' => $remise,
                'remise_pourcentage' => $remisePourcentage,
                'mode_paiement' => $data['modePaiement'],
                'montant_remis' => $montantRemis,
            ];

            if ($editingId) {
                $vente = Vente::findOrFail($editingId);
                $vente->update($attributs);
                $ancienArticleIds = $vente->lignes()->pluck('article_id')->unique();
                $vente->lignes()->delete();
            } else {
                $vente = Vente::create($attributs + [
                    'numero' => NumeroGenerator::suivant('ventes', 'VEN', auth()->user()->entreprise_id),
                ]);
                $ancienArticleIds = collect();
            }

            foreach ($data['lignes'] as $ligne) {
                VenteLigne::create([
                    'vente_id' => $vente->id,
                    'article_id' => $ligne['article_id'],
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                    'total' => $ligne['quantite'] * $ligne['prix_unitaire'],
                ]);
            }

            $articleIdsATraiter = $ancienArticleIds->merge(array_column($data['lignes'], 'article_id'))->unique();

            foreach (Article::whereIn('id', $articleIdsATraiter)->get() as $article) {
                $article->recalculerStock();
            }

            // Le client n'a pas pu recevoir sa monnaie sur le moment (pas assez de
            // petites coupures en caisse) : on la consigne pour la lui rendre plus
            // tard, plutot que de la perdre ou de forcer une remise non voulue.
            if ($monnaieNonRendue && $monnaie > 0) {
                MonnaieARendre::create([
                    'client_id' => $vente->client_id,
                    'vente_id' => $vente->id,
                    'date' => $vente->date,
                    'montant' => $monnaie,
                    'statut' => 'due',
                ]);
            }

            PaiementVenteSynchroniseur::synchroniser($vente);
            JournalVenteSynchroniseur::synchroniser($vente->fresh());

            return $vente;
        });

        $etaitEnModification = (bool) $editingId;

        // Le panier reste ouvert, pret pour la vente suivante, plutot que de se
        // refermer : ca evite de recliquer sur "+ Nouvelle vente" a chaque client.
        $this->resetForm();
        $this->showForm = true;
        session()->flash('status', $etaitEnModification ? 'Vente modifiee avec succes.' : 'Vente enregistree avec succes.');
        $this->dispatch('vente-enregistree', venteId: $vente->id);
    }

    public function annuler(): void
    {
        if ($this->editingId) {
            $this->redirectRoute('ventes.historique');

            return;
        }

        $this->resetForm();
        $this->showForm = true;
    }

    private function ligneVide(): array
    {
        return ['article_id' => null, 'quantite' => 1, 'prix_unitaire' => 0, 'recherche' => ''];
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'client_id', 'clientRecherche', 'lignes', 'remisePourcentage', 'modifier_id', 'montantRemis', 'monnaieNonRendue']);
        $this->date = now()->toDateString();
        $this->remisePourcentage = '0';
        $this->modePaiement = 'Especes';
        $this->lignes = [$this->ligneVide()];
        $this->resetErrorBag();
    }

    /**
     * Genere un code court a partir de la designation (ex. "Stylo bleu" -> "STYLOB"),
     * en ajoutant un suffixe numerique en cas de collision.
     */
    private function genererCodeArticle(string $designation): string
    {
        $base = mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $designation));
        $base = mb_substr($base, 0, 6) ?: 'ART';

        $code = $base;
        $suffixe = 1;

        while (Article::where('code', $code)->exists()) {
            $suffixe++;
            $code = $base.$suffixe;
        }

        return $code;
    }

    public function render()
    {
        return view('livewire.ventes', [
            'articlesDisponibles' => Article::orderBy('designation')->get(),
        ])->layout('layouts.app', ['title' => 'Ventes']);
    }
}
