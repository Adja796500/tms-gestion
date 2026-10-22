<?php

namespace App\Livewire;

use App\Models\Achat;
use App\Models\Article;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\MonnaieARendre;
use App\Models\PaiementClient;
use App\Models\ReglementFournisseur;
use App\Models\Vente;
use App\Models\VenteLigne;
use App\Support\Finances;
use Carbon\CarbonImmutable;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * Le classeur Excel d'origine ouvrait la meme feuille ACCUEIL a tous les profils
     * ayant acces au module, sans distinction de contenu. Ici on va plus loin : chaque
     * profil operationnel ne voit que les indicateurs utiles a son metier ; les profils
     * de pilotage (Administrateur, Directeur, Comptable) et Consultation (lecture seule
     * deja autorisee sur la quasi-totalite des modules) gardent la vue complete.
     */
    private const WIDGETS_PAR_PROFIL = [
        'Caissier' => ['a_traiter', 'encaissements', 'nb_factures', 'nb_clients', 'dernieres_ventes'],
        'Magasinier' => ['a_traiter', 'valeur_stock', 'nb_articles', 'alertes_stock', 'total_achats', 'meilleurs_articles', 'nb_fournisseurs', 'dernieres_ventes'],
        'Commercial' => ['a_traiter', 'chiffre_affaires', 'nb_clients', 'meilleurs_clients', 'meilleurs_articles', 'dernieres_ventes'],
    ];

    private const TOUS_LES_WIDGETS = [
        'mois_en_cours', 'graphiques', 'a_traiter',
        'chiffre_affaires', 'total_achats', 'creances', 'dettes', 'benefice', 'valeur_stock',
        'encaissements', 'nb_factures', 'nb_articles', 'nb_clients', 'nb_fournisseurs',
        'alertes_stock', 'dernieres_ventes', 'meilleurs_clients', 'meilleurs_articles',
    ];

    /**
     * Points qui demandent une action, chacun visible seulement si le profil a acces
     * au module concerne. Le classeur obligeait a parcourir chaque feuille pour les voir.
     *
     * @return array<int, array{label: string, valeur: string, route: string, couleur: string}>
     */
    private function aTraiter($articlesEnAlerte): array
    {
        $user = auth()->user();
        $points = [];

        if ($user->peutAccederA('REGISTRE_DOCUMENTS')) {
            $factures = Facture::where('type', Facture::TYPE_DEFINITIVE)
                ->where('statut', '!=', 'Annulée')
                ->withSum('paiements', 'montant')
                ->get()
                ->filter(fn (Facture $f) => (float) $f->montant_total - (float) $f->paiements_sum_montant > 0);
            if ($factures->isNotEmpty()) {
                $reste = $factures->sum(fn (Facture $f) => (float) $f->montant_total - (float) $f->paiements_sum_montant);
                $points[] = ['label' => $factures->count().' facture(s) non soldée(s)', 'valeur' => number_format($reste, 0, ',', ' ').' '.config('app.currency'), 'route' => 'registre-documents.index', 'couleur' => 'rose'];
            }
        }

        if ($user->peutAccederA('ARTICLES') && $articlesEnAlerte->isNotEmpty()) {
            $points[] = ['label' => $articlesEnAlerte->count().' article(s) en stock bas', 'valeur' => 'À réapprovisionner', 'route' => 'articles.index', 'couleur' => 'amber'];
        }

        if ($user->peutAccederA(Ventes::MODULES_CAISSE)) {
            $monnaies = MonnaieARendre::where('statut', 'due');
            if ((clone $monnaies)->exists()) {
                $points[] = ['label' => (clone $monnaies)->count().' monnaie(s) à rendre', 'valeur' => number_format((float) $monnaies->sum('montant'), 0, ',', ' ').' '.config('app.currency'), 'route' => 'monnaies-a-rendre.index', 'couleur' => 'sky'];
            }
        }

        if ($user->peutAccederA('COMMANDES')) {
            $commandes = Commande::whereIn('statut', ['En_attente', 'En_cours'])->count();
            if ($commandes > 0) {
                $points[] = ['label' => $commandes.' commande(s) en attente ou en cours', 'valeur' => 'À livrer', 'route' => 'commandes.index', 'couleur' => 'violet'];
            }
        }

        return $points;
    }

    public function render(Finances $finances)
    {
        $profil = auth()->user()->profil;
        $widgets = self::WIDGETS_PAR_PROFIL[$profil] ?? self::TOUS_LES_WIDGETS;
        // Chiffre d'affaires et benefice : ceux du Journal (ventes + services), comme la
        // Synthese et la feuille SYNTHESE du classeur, pour afficher partout le meme chiffre.
        $resultatGlobal = $finances->resultat();
        $chiffreAffaires = $resultatGlobal['entrees'];
        $totalVentes = (float) Vente::sum('total');
        $totalAchats = (float) Achat::sum('total');
        $totalPaiements = (float) PaiementClient::sum('montant');
        $totalReglements = (float) ReglementFournisseur::sum('montant');

        // Memes regles que les modules Creances clients et Dettes fournisseurs.
        $facturesDefinitives = (float) Facture::where('type', Facture::TYPE_DEFINITIVE)->where('statut', '!=', 'Annulée')->sum('montant_total');
        $creances = round($totalVentes + $facturesDefinitives - $totalPaiements, 2);
        $dettes = round($totalAchats - (float) Achat::sum('montant_regle') - (float) ReglementFournisseur::whereNull('achat_id')->sum('montant'), 2);

        $articles = Article::all();
        $articlesEnAlerte = $articles->filter(fn (Article $a) => $a->estEnAlerte());
        $valeurStock = $articles->sum(fn (Article $a) => $a->stock_disponible * $a->prix_achat);

        $meilleursClients = Vente::query()
            ->selectRaw('client_id, SUM(total) as total_ventes')
            ->groupBy('client_id')
            ->orderByDesc('total_ventes')
            ->with('client')
            ->take(5)
            ->get();

        $meilleursArticles = VenteLigne::query()
            ->whereHas('vente')
            ->selectRaw('article_id, SUM(quantite) as quantite_totale, SUM(total) as total_ventes')
            ->groupBy('article_id')
            ->orderByDesc('total_ventes')
            ->with('article')
            ->take(5)
            ->get();

        // Evolution sur 12 mois et comparaison au mois precedent (graphiques de l'ACCUEIL).
        $evolution = $finances->syntheseMensuelle()->take(-12)->values();
        $debutMois = CarbonImmutable::now()->startOfMonth();
        $moisEnCours = $finances->resultat($debutMois, $debutMois->endOfMonth());
        $moisPrecedent = $finances->resultat($debutMois->subMonth(), $debutMois->subMonth()->endOfMonth());

        return view('livewire.dashboard', [
            'evolution' => $evolution,
            'moisEnCours' => $moisEnCours,
            'moisPrecedent' => $moisPrecedent,
            'libelleMoisEnCours' => Finances::libelleMois($debutMois),
            'activites' => $finances->parCategorie(CarbonImmutable::now()->startOfYear())->where('entrees', '>', 0)->sortByDesc('entrees')->values(),
            'soldes' => $finances->soldes(),
            'aTraiter' => in_array('a_traiter', $widgets) ? $this->aTraiter($articlesEnAlerte) : [],
            'widgets' => $widgets,
            'chiffreAffaires' => $chiffreAffaires,
            'totalAchats' => $totalAchats,
            'totalPaiements' => $totalPaiements,
            'tresorerie' => round($totalPaiements - $totalReglements, 2),
            'creances' => $creances,
            'dettes' => $dettes,
            'benefice' => round($resultatGlobal['benefice'], 2),
            'valeurStock' => round($valeurStock, 2),
            'nbFactures' => Facture::count(),
            'nbClients' => Client::count(),
            'nbFournisseurs' => Fournisseur::count(),
            'nbArticles' => Article::count(),
            'articlesEnAlerte' => $articlesEnAlerte,
            'meilleursClients' => $meilleursClients,
            'meilleursArticles' => $meilleursArticles,
            'dernieresVentes' => Vente::with(['client', 'lignes.article'])->latest('date')->latest('id')->take(6)->get(),
        ])->layout('layouts.app', ['title' => 'Tableau de bord']);
    }
}
