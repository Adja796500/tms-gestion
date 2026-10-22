<?php

namespace App\Support;

use App\Models\Achat;
use App\Models\Acquisition;
use App\Models\EcritureJournal;
use App\Models\PaiementClient;
use App\Models\ParametreEntreprise;
use App\Models\ReglementFournisseur;
use App\Models\Vente;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Reprend les formules des feuilles TRESORERIE, SYNTHESE, RAPPORT MENSUEL et
 * STATISTIQUES du classeur. Toutes les requetes passent par Eloquent, donc
 * restent cloisonnees a l'entreprise de l'utilisateur connecte.
 *
 * Deux notions distinctes, comme dans le classeur :
 * - le resultat (Entrees / Depenses / Benefice) vient du Journal des transactions,
 *   ventes comprises ;
 * - la tresorerie (Caisse / Banque) suit les mouvements d'argent reels : Journal
 *   hors ventes + paiements clients en entree ; Journal (depenses) + acquisitions
 *   + achats regles + reglements fournisseurs en sortie. Les ventes passent par
 *   leur paiement client (une vente a credit n'a pas encore rempli la caisse).
 */
class Finances
{
    public const POSTES = [
        'investissement' => 'Investissement',
        'epargne' => 'Épargne',
        'loyer' => 'Loyer',
        'fonctionnement' => 'Fonctionnement',
    ];

    public const TYPES_ACQUISITION = [
        'Acquisition' => 'Acquisition',
        'Pret_Benefices' => 'Prêt Bénéfices',
        'Depense_Reserve' => 'Dépense réserve',
    ];

    private ?ParametreEntreprise $parametres;

    public function __construct()
    {
        $this->parametres = ParametreEntreprise::first();
    }

    public static function libelleMois(CarbonInterface $mois): string
    {
        return ucfirst($mois->locale('fr')->translatedFormat('F Y'));
    }

    /**
     * Abreviation sans ambiguite pour les axes de graphique ("Juin 26", "Juil. 26") :
     * les 3 premieres lettres confondaient juin et juillet.
     */
    public static function moisCourt(CarbonInterface $mois): string
    {
        return ['Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'][$mois->month - 1].' '.$mois->format('y');
    }

    /**
     * Taux de repartition du benefice (saisis en % dans Configuration entreprise).
     *
     * @return array<string, float> poste => fraction (0.55 pour 55 %)
     */
    public function taux(): array
    {
        return collect(self::POSTES)->keys()
            ->mapWithKeys(fn ($poste) => [$poste => (float) ($this->parametres?->{'taux_'.$poste} ?? 0) / 100])
            ->all();
    }

    /**
     * @return array<string, float>
     */
    public function repartition(float $benefice): array
    {
        return collect($this->taux())->map(fn ($taux) => round($benefice * $taux, 2))->all();
    }

    /**
     * @return array{Caisse: float, Banque: float}
     */
    public function soldesInitiaux(): array
    {
        return [
            'Caisse' => (float) ($this->parametres?->solde_initial_caisse ?? 0),
            'Banque' => (float) ($this->parametres?->solde_initial_banque ?? 0),
        ];
    }

    /**
     * Mois couverts par les donnees, du plus ancien mouvement au mois courant.
     *
     * @return Collection<int, CarbonImmutable>
     */
    public function mois(): Collection
    {
        $dates = collect([
            EcritureJournal::min('date'),
            Vente::min('date'),
            Achat::min('date'),
            PaiementClient::min('date'),
            Acquisition::min('date'),
        ])->filter();

        $fin = CarbonImmutable::now()->startOfMonth();
        $debut = $dates->isEmpty() ? $fin : CarbonImmutable::parse($dates->min())->startOfMonth();

        $mois = collect();
        for ($m = $debut; $m <= $fin; $m = $m->addMonth()) {
            $mois->push($m);
        }

        return $mois;
    }

    /**
     * Entrees et sorties d'argent par compte sur une periode (bornes incluses,
     * null = sans limite).
     *
     * @return array{Caisse: array{entrees: float, sorties: float}, Banque: array{entrees: float, sorties: float}}
     */
    public function flux(?CarbonImmutable $debut = null, ?CarbonImmutable $fin = null): array
    {
        $flux = ['Caisse' => ['entrees' => 0.0, 'sorties' => 0.0], 'Banque' => ['entrees' => 0.0, 'sorties' => 0.0]];
        $periode = function ($query) use ($debut, $fin) {
            return $query
                ->when($debut, fn ($q) => $q->whereDate('date', '>=', $debut))
                ->when($fin, fn ($q) => $q->whereDate('date', '<=', $fin));
        };
        $ajouter = function (string $compte, string $sens, $montant) use (&$flux) {
            $compte = $compte === 'Banque' ? 'Banque' : 'Caisse';
            $flux[$compte][$sens] += (float) $montant;
        };

        $journal = $periode(EcritureJournal::query()->whereNull('vente_id'))
            ->selectRaw('compte, type, SUM(montant) as total')
            ->groupBy('compte', 'type')
            ->get();
        foreach ($journal as $ligne) {
            $ajouter($ligne->compte, $ligne->type === 'Entree' ? 'entrees' : 'sorties', $ligne->total);
        }

        foreach ($periode(PaiementClient::query())->selectRaw('mode_paiement, SUM(montant) as total')->groupBy('mode_paiement')->get() as $ligne) {
            $ajouter(CompteResolver::depuisMoyenPaiement((string) $ligne->mode_paiement), 'entrees', $ligne->total);
        }

        foreach ($periode(ReglementFournisseur::query())->selectRaw('mode_paiement, SUM(montant) as total')->groupBy('mode_paiement')->get() as $ligne) {
            $ajouter(CompteResolver::depuisMoyenPaiement((string) $ligne->mode_paiement), 'sorties', $ligne->total);
        }

        foreach ($periode(Acquisition::query())->selectRaw('compte, SUM(montant) as total')->groupBy('compte')->get() as $ligne) {
            $ajouter((string) $ligne->compte, 'sorties', $ligne->total);
        }

        // Les achats n'ont pas de moyen de paiement : la part reglee A L'ACHAT sort de la
        // caisse. montant_regle inclut aussi les reglements rattaches ensuite a l'achat,
        // deja comptes ci-dessus a leur date : on les retire pour ne pas les compter deux fois.
        $achats = $periode(Achat::query())->withSum('reglements', 'montant')->get(['id', 'montant_regle']);
        $ajouter('Caisse', 'sorties', $achats->sum(fn ($a) => (float) $a->montant_regle - (float) $a->reglements_sum_montant));

        return $flux;
    }

    /**
     * @return array{Caisse: float, Banque: float, Total: float}
     */
    public function soldes(?CarbonImmutable $jusquau = null): array
    {
        $initiaux = $this->soldesInitiaux();
        $flux = $this->flux(null, $jusquau);

        $caisse = $initiaux['Caisse'] + $flux['Caisse']['entrees'] - $flux['Caisse']['sorties'];
        $banque = $initiaux['Banque'] + $flux['Banque']['entrees'] - $flux['Banque']['sorties'];

        return ['Caisse' => round($caisse, 2), 'Banque' => round($banque, 2), 'Total' => round($caisse + $banque, 2)];
    }

    /**
     * Resultat du Journal (ventes comprises) sur une periode.
     *
     * @return array{entrees: float, depenses: float, benefice: float, nb: int}
     */
    public function resultat(?CarbonImmutable $debut = null, ?CarbonImmutable $fin = null): array
    {
        $lignes = EcritureJournal::query()
            ->when($debut, fn ($q) => $q->whereDate('date', '>=', $debut))
            ->when($fin, fn ($q) => $q->whereDate('date', '<=', $fin))
            ->selectRaw('type, SUM(montant) as total, COUNT(*) as nb')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $entrees = (float) ($lignes['Entree']->total ?? 0);
        $depenses = (float) ($lignes['Depense']->total ?? 0);

        return [
            'entrees' => $entrees,
            'depenses' => $depenses,
            'benefice' => $entrees - $depenses,
            'nb' => (int) $lignes->sum('nb'),
        ];
    }

    /**
     * Detail par categorie d'activite (Journal), trie par nom.
     *
     * @return Collection<int, array{categorie: string, entrees: float, depenses: float, benefice: float}>
     */
    public function parCategorie(?CarbonImmutable $debut = null, ?CarbonImmutable $fin = null): Collection
    {
        return EcritureJournal::query()
            ->leftJoin('categories_activite', 'categories_activite.id', '=', 'ecritures_journal.categorie_activite_id')
            ->when($debut, fn ($q) => $q->whereDate('ecritures_journal.date', '>=', $debut))
            ->when($fin, fn ($q) => $q->whereDate('ecritures_journal.date', '<=', $fin))
            ->selectRaw("COALESCE(categories_activite.nom, 'Sans catégorie') as categorie")
            ->selectRaw("SUM(CASE WHEN ecritures_journal.type = 'Entree' THEN ecritures_journal.montant ELSE 0 END) as entrees")
            ->selectRaw("SUM(CASE WHEN ecritures_journal.type = 'Depense' THEN ecritures_journal.montant ELSE 0 END) as depenses")
            ->groupByRaw("COALESCE(categories_activite.nom, 'Sans catégorie')")
            ->orderBy('categorie')
            ->get()
            ->map(fn ($l) => [
                'categorie' => $l->categorie,
                'entrees' => (float) $l->entrees,
                'depenses' => (float) $l->depenses,
                'benefice' => (float) $l->entrees - (float) $l->depenses,
            ]);
    }

    /**
     * Totaux des acquisitions / prets sur benefices / depenses sur reserve.
     *
     * @return array<string, float>
     */
    public function acquisitionsParType(?CarbonImmutable $debut = null, ?CarbonImmutable $fin = null): array
    {
        $totaux = Acquisition::query()
            ->when($debut, fn ($q) => $q->whereDate('date', '>=', $debut))
            ->when($fin, fn ($q) => $q->whereDate('date', '<=', $fin))
            ->selectRaw('type, SUM(montant) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return collect(self::TYPES_ACQUISITION)->keys()
            ->mapWithKeys(fn ($type) => [$type => (float) ($totaux[$type] ?? 0)])
            ->all();
    }

    /**
     * Une ligne par mois : resultat du Journal, repartition, acquisitions,
     * reste a investir et mouvements de tresorerie (SYNTHESE + TRESORERIE).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function syntheseMensuelle(): Collection
    {
        $initiaux = $this->soldesInitiaux();
        $cumul = $initiaux['Caisse'] + $initiaux['Banque'];

        return $this->mois()->map(function (CarbonImmutable $mois) use (&$cumul) {
            $fin = $mois->endOfMonth();
            $resultat = $this->resultat($mois, $fin);
            $repartition = $this->repartition($resultat['benefice']);
            $acquisitions = $this->acquisitionsParType($mois, $fin)['Acquisition'];
            $flux = $this->flux($mois, $fin);

            $totalEntrees = $flux['Caisse']['entrees'] + $flux['Banque']['entrees'];
            $totalSorties = $flux['Caisse']['sorties'] + $flux['Banque']['sorties'];
            $cumul += $totalEntrees - $totalSorties;

            return [
                'mois' => $mois,
                'libelle' => self::libelleMois($mois),
                'entrees' => $resultat['entrees'],
                'depenses' => $resultat['depenses'],
                'benefice' => $resultat['benefice'],
                'repartition' => $repartition,
                'acquisitions' => $acquisitions,
                'reste_a_investir' => $repartition['investissement'] - $acquisitions,
                'flux' => $flux,
                'total_entrees_tresorerie' => $totalEntrees,
                'total_sorties_tresorerie' => $totalSorties,
                'solde_mois' => $totalEntrees - $totalSorties,
                'solde_cumule' => $cumul,
            ];
        });
    }
}
