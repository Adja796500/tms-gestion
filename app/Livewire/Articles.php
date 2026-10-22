<?php

namespace App\Livewire;

use App\Models\Achat;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Support\NumeroGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Articles extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $search = '';

    public ?int $editingId = null;

    #[Validate('required|string|max:50')]
    public string $code = '';

    #[Validate('required|string|max:255')]
    public string $designation = '';

    /** Code-barres imprime sur le produit (EAN...), lu par douchette ou camera a la caisse. */
    public string $code_barre = '';

    #[Validate('nullable|string|max:100')]
    public string $categorie = '';

    #[Validate('nullable|string|max:50')]
    public string $unite = '';

    #[Validate('nullable|numeric|min:0')]
    public ?string $prix_achat = null;

    #[Validate('required|numeric|min:0')]
    public string $prix_vente = '0';

    #[Validate('required|integer|min:0')]
    public string $stock_initial = '0';

    #[Validate('required|integer|min:0')]
    public string $stock_minimum = '0';

    public bool $showForm = false;

    /** Photo envoyee dans le formulaire (fichier temporaire Livewire). */
    public $photo = null;

    /** Photo deja enregistree de l'article en cours de modification. */
    public ?string $photoActuelle = null;

    public bool $retirerPhoto = false;

    /**
     * Livraisons saisies a la creation d'un article : qui a livre quoi, et quand.
     * Un meme article peut avoir plusieurs fournisseurs, d'ou le tableau de lignes
     * (meme principe que le panier des ventes).
     *
     * @var array<int, array{fournisseur_id: int|string|null, fournisseurRecherche: string, date: string, quantite: string, prix_achat: string}>
     */
    public array $livraisons = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updated(string $name, $value): void
    {
        if (preg_match('/^livraisons\.(\d+)\.fournisseurRecherche$/', $name, $matches)) {
            $index = (int) $matches[1];
            $fournisseur = $this->resoudreFournisseur((string) $value);
            $this->livraisons[$index]['fournisseur_id'] = $fournisseur?->id;
        }
    }

    /**
     * Une saisie partielle qui ne cible plus qu'un seul fournisseur est acceptee
     * directement, comme pour le client/article dans le panier des ventes.
     */
    private function resoudreFournisseur(string $texte): ?Fournisseur
    {
        $matches = $this->fournisseursCorrespondants($texte);

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * @return Collection<int, Fournisseur>
     */
    public function fournisseursCorrespondants(string $texte): Collection
    {
        $texte = trim($texte);

        if ($texte === '') {
            return collect();
        }

        return Fournisseur::where('nom', 'ilike', "%{$texte}%")->limit(6)->get();
    }

    /**
     * Alimente le menu deroulant personnalise des fournisseurs : correspondances a
     * la saisie, ou les premiers fournisseurs si le champ est encore vide, pour
     * pouvoir parcourir la liste au clic meme apres avoir deja choisi.
     *
     * @return Collection<int, Fournisseur>
     */
    public function fournisseursPourListe(?string $texte = null): Collection
    {
        $texte = trim($texte ?? '');

        return Fournisseur::query()
            ->when($texte !== '', fn ($q) => $q->where('nom', 'ilike', "%{$texte}%"))
            ->orderBy('nom')
            ->limit(20)
            ->get();
    }

    public function selectionnerFournisseur(int $index, int $id): void
    {
        abort_unless(auth()->user()->peutModifier('ARTICLES'), 403);

        $fournisseur = Fournisseur::find($id);

        if ($fournisseur && isset($this->livraisons[$index])) {
            $this->livraisons[$index]['fournisseur_id'] = $fournisseur->id;
            $this->livraisons[$index]['fournisseurRecherche'] = $fournisseur->nom;
        }
    }

    public function ajouterLivraison(): void
    {
        $this->livraisons[] = $this->livraisonVide();
    }

    public function retirerLivraison(int $index): void
    {
        unset($this->livraisons[$index]);
        $this->livraisons = array_values($this->livraisons);
    }

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier('ARTICLES'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function modifier(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('ARTICLES'), 403);

        $article = Article::findOrFail($id);
        $this->editingId = $article->id;
        $this->code = $article->code;
        $this->code_barre = (string) $article->code_barre;
        $this->designation = $article->designation;
        $this->categorie = (string) $article->categorie;
        $this->unite = (string) $article->unite;
        $this->prix_achat = $article->prix_achat !== null ? (string) $article->prix_achat : null;
        $this->prix_vente = (string) $article->prix_vente;
        $this->stock_initial = (string) $article->stock_initial;
        $this->stock_minimum = (string) $article->stock_minimum;
        $this->photoActuelle = $article->photoUrl();
        // Les livraisons ne se saisissent qu'a la creation d'un article : on vide le
        // tableau pour ne pas rejouer une saisie laissee en suspens par un
        // "Nouvel article" precedent qu'on n'aurait pas soumis.
        $this->livraisons = [];
        $this->showForm = true;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('ARTICLES'), 403);

        // Un nom de fournisseur saisi qui ne correspond a rien d'existant est
        // enregistre automatiquement ici, comme pour le client/article des ventes.
        // (En modification, $this->livraisons est deja vide : voir modifier().)
        foreach ($this->livraisons as $index => $livraison) {
            if ($livraison['fournisseur_id'] || trim($livraison['fournisseurRecherche'] ?? '') === '') {
                continue;
            }

            if ($this->fournisseursCorrespondants($livraison['fournisseurRecherche'])->count() > 1) {
                $this->addError("livraisons.{$index}.fournisseur_id", 'Plusieurs fournisseurs correspondent a cette saisie : precisez.');

                continue;
            }

            $fournisseur = Fournisseur::create(['nom' => trim($livraison['fournisseurRecherche'])]);
            $this->livraisons[$index]['fournisseur_id'] = $fournisseur->id;
            $this->livraisons[$index]['fournisseurRecherche'] = $fournisseur->nom;
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $data = $this->validate([
            'code' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('articles', 'code')
                ->where('entreprise_id', auth()->user()->entreprise_id)->ignore($this->editingId)],
            'designation' => 'required|string|max:255',
            'code_barre' => ['nullable', 'string', 'max:64', \Illuminate\Validation\Rule::unique('articles', 'code_barre')
                ->where('entreprise_id', auth()->user()->entreprise_id)->ignore($this->editingId)],
            'categorie' => 'nullable|string|max:100',
            'unite' => 'nullable|string|max:50',
            'prix_achat' => 'nullable|numeric|min:0',
            'prix_vente' => 'required|numeric|min:0',
            'stock_initial' => 'required|integer|min:0',
            'stock_minimum' => 'required|integer|min:0',
            'photo' => 'nullable|image|max:3072',
            'livraisons.*.fournisseur_id' => 'nullable|existe_ici:fournisseurs',
            'livraisons.*.date' => 'nullable|date',
            'livraisons.*.quantite' => 'nullable|integer|min:0',
            'livraisons.*.prix_achat' => 'nullable|numeric|min:0',
        ]);

        $livraisons = $this->livraisons;

        $existant = $this->editingId ? Article::find($this->editingId) : null;
        $photoPath = $existant?->photo_path;
        if (($this->photo || $this->retirerPhoto) && $photoPath) {
            Storage::disk('public')->delete($photoPath);
            $photoPath = null;
        }
        if ($this->photo) {
            $photoPath = $this->photo->store('articles', 'public');
        }

        $article = DB::transaction(function () use ($data, $livraisons, $photoPath) {
            $article = Article::updateOrCreate(['id' => $this->editingId], [
                'photo_path' => $photoPath,
                'code' => $data['code'],
                'designation' => $data['designation'],
                'code_barre' => trim((string) $data['code_barre']) ?: null,
                'categorie' => $data['categorie'],
                'unite' => $data['unite'],
                'prix_achat' => $data['prix_achat'],
                'prix_vente' => $data['prix_vente'],
                'stock_initial' => $data['stock_initial'],
                'stock_minimum' => $data['stock_minimum'],
            ]);

            foreach ($livraisons as $livraison) {
                $quantite = (int) ($livraison['quantite'] ?: 0);

                if (! $livraison['fournisseur_id'] || $quantite <= 0) {
                    continue;
                }

                $prixUnitaire = (float) ($livraison['prix_achat'] ?: ($data['prix_achat'] ?: 0));
                $total = $quantite * $prixUnitaire;

                $achat = Achat::create([
                    'numero' => NumeroGenerator::suivant('achats', 'ACH', auth()->user()->entreprise_id),
                    'date' => $livraison['date'] ?: now()->toDateString(),
                    'article_id' => $article->id,
                    'fournisseur_id' => $livraison['fournisseur_id'],
                    'quantite' => $quantite,
                    'prix_unitaire' => $prixUnitaire,
                    'total' => $total,
                    'mode_reglement' => 'Comptant',
                    'montant_regle' => $total,
                ]);
                $achat->rafraichirStatut();
            }

            $article->recalculerStock();

            return $article;
        });

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', "Article {$article->code} enregistré avec succès.");
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('ARTICLES'), 403);

        $article = Article::findOrFail($id);
        if ($article->photo_path) {
            Storage::disk('public')->delete($article->photo_path);
        }
        $article->delete();
        session()->flash('status', 'Article supprimé.');
    }

    public function annuler(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function livraisonVide(): array
    {
        return [
            'fournisseur_id' => null,
            'fournisseurRecherche' => '',
            'date' => now()->toDateString(),
            'quantite' => '',
            'prix_achat' => '',
        ];
    }

    private function resetForm(): void
    {
        $this->reset(['photo', 'photoActuelle', 'retirerPhoto', 'code_barre', 'editingId', 'code', 'designation', 'categorie', 'unite', 'prix_achat', 'prix_vente', 'stock_initial', 'stock_minimum', 'livraisons']);
        $this->prix_vente = '0';
        $this->stock_initial = '0';
        $this->stock_minimum = '0';
        $this->livraisons = [$this->livraisonVide()];
        $this->resetErrorBag();
    }

    public function render()
    {
        $articles = Article::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('code', 'ilike', "%{$this->search}%")
                        ->orWhere('code_barre', $this->search)
                        ->orWhere('designation', 'ilike', "%{$this->search}%");
                });
            })
            ->orderBy('designation')
            ->paginate(10);

        return view('livewire.articles', ['articles' => $articles])
            ->layout('layouts.app', ['title' => 'Articles & stock']);
    }
}
