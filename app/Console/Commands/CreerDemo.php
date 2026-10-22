<?php

namespace App\Console\Commands;

use App\Models\Achat;
use App\Models\Acquisition;
use App\Models\ArchiveMensuelle;
use App\Models\Article;
use App\Models\CategorieActivite;
use App\Models\Client;
use App\Models\Commande;
use App\Models\EcritureJournal;
use App\Models\Entreprise;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\MonnaieARendre;
use App\Models\PaiementClient;
use App\Models\ParametreEntreprise;
use App\Models\Recu;
use App\Models\ReglementFournisseur;
use App\Models\User;
use App\Models\Vente;
use App\Models\VenteLigne;
use App\Support\CompteResolver;
use App\Support\Finances;
use App\Support\JournalVenteSynchroniseur;
use App\Support\NumeroGenerator;
use App\Support\PaiementVenteSynchroniseur;
use Carbon\CarbonImmutable;
use Database\Seeders\AccessRuleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Cree une entreprise de DEMONSTRATION avec 4 mois d'activite coherente (ventes,
 * services, achats, factures payees ou en retard, photos d'articles...) pour
 * presenter la plateforme sans toucher aux vraies donnees des clients.
 * Tout passe par la logique de l'application (stock, journal, paiements).
 */
class CreerDemo extends Command
{
    public const NOM = 'Entreprise Démo';
    public const MOT_DE_PASSE = 'Demo2026!';

    protected $signature = 'demo:creer {--reinitialiser : Supprime puis recree l\'entreprise de demonstration}';

    protected $description = 'Crée l\'entreprise de démonstration (identifiants : demo / demo-caisse, mot de passe Demo2026!)';

    protected int $entrepriseId;

    /** Premier jour de la periode d'activite generee. */
    protected CarbonImmutable $debut;

    /** Sigle utilise dans le numero des factures (NNN/SIGLE/ANNEE). */
    protected string $sigle = 'DEMO';

    public function handle(): int
    {
        $existante = Entreprise::where('nom', self::NOM)->first();

        if ($existante && ! $this->option('reinitialiser')) {
            $this->warn('L\'entreprise de démonstration existe déjà. Relancez avec --reinitialiser pour la recréer.');

            return self::FAILURE;
        }

        if ($existante) {
            $this->supprimer($existante);
        }

        // Repartir sans utilisateur connecte (une generation precedente dans le meme
        // processus aurait laisse celui de l'ancienne demo, faussant le cloisonnement).
        auth()->forgetUser();
        mt_srand(2026); // memes donnees a chaque generation
        $this->debut = CarbonImmutable::now()->subMonths(4)->startOfMonth();

        DB::transaction(function () {
            $this->creerEntreprise();
            $categories = $this->creerCategories();
            [$clients, $fournisseurs] = $this->creerPartenaires();
            $articles = $this->creerArticles($fournisseurs);
            $this->creerVentes($clients, $articles);
            $this->creerServicesEtDepenses($categories);
            $this->creerDocuments($clients, $fournisseurs);
            $this->creerDivers($clients);

            foreach ($articles as $article) {
                $article->recalculerStock();
            }
            $this->archiverMoisPasses();
        });

        $this->info('Entreprise de démonstration créée.');
        $this->table(['Profil', 'Utilisateur', 'Mot de passe'], [
            ['Administrateur', 'demo', self::MOT_DE_PASSE],
            ['Caissier', 'demo-caisse', self::MOT_DE_PASSE],
        ]);

        return self::SUCCESS;
    }

    private function supprimer(Entreprise $entreprise): void
    {
        $dossierLogo = 'demo/'.$entreprise->id;
        Storage::disk('public')->deleteDirectory($dossierLogo);
        DB::table('document_sequences')->where('entreprise_id', $entreprise->id)->delete();
        User::withoutGlobalScopes()->where('entreprise_id', $entreprise->id)->delete();
        $entreprise->delete(); // le reste suit par suppression en cascade
        $this->line('Ancienne démonstration supprimée.');
    }

    private function creerEntreprise(): void
    {
        $entreprise = Entreprise::create(['nom' => self::NOM, 'actif' => true]);
        $this->entrepriseId = $entreprise->id;

        $admin = User::create([
            'entreprise_id' => $entreprise->id, 'name' => 'Awa Sawadogo', 'username' => 'demo',
            'password' => Hash::make(self::MOT_DE_PASSE), 'profil' => 'Administrateur', 'actif' => true,
        ]);
        User::create([
            'entreprise_id' => $entreprise->id, 'name' => 'Ibrahim Ouédraogo', 'username' => 'demo-caisse',
            'password' => Hash::make(self::MOT_DE_PASSE), 'profil' => 'Caissier', 'actif' => true,
        ]);
        (new AccessRuleSeeder())->run($entreprise->id);

        // Les creations suivantes se font "au nom" de l'administrateur de la demo :
        // cloisonnement et rattachement automatiques a l'entreprise, comme dans l'application.
        auth()->setUser($admin);

        ParametreEntreprise::create([
            'entreprise_id' => $entreprise->id,
            'nom_entreprise' => self::NOM.' — Services & Commerce',
            'sigle' => 'DEMO',
            'logo_path' => $this->dessinerLogo(),
            'activites_texte' => 'Imprimerie • Infographie • Sérigraphie • Photographie • Fournitures de bureau',
            'telephone' => '(+226) 70 00 00 00',
            'email' => 'contact@exemple-demo.bf',
            'ville' => 'Ouagadougou',
            'adresse' => 'Avenue Kwame Nkrumah, Ouagadougou',
            'regime_fiscal' => 'CME',
            'division_fiscale' => 'Ouaga 1',
            'rccm' => 'BF-OUA-DEMO-0001',
            'ifu' => '00000000X',
            'signataire' => 'Awa Sawadogo',
            'taux_investissement' => 55, 'taux_epargne' => 10, 'taux_loyer' => 5, 'taux_fonctionnement' => 30,
            'solde_initial_caisse' => 1500000,
            'solde_initial_banque' => 800000,
        ]);
    }

    /**
     * @return array<string, CategorieActivite>
     */
    private function creerCategories(): array
    {
        $categories = [];
        foreach (['ABA', 'Photographie', 'Infographie', 'Caligraphie', 'Sérigraphie', 'Vente', 'Autres'] as $nom) {
            $categories[$nom] = CategorieActivite::create(['nom' => $nom, 'actif' => true]);
        }

        return $categories;
    }

    private function creerPartenaires(): array
    {
        $clients = collect([
            ['Lycée Wend-Manegda', '70112233'], ['Pharmacie du Progrès', '76223344'], ['Mariam Kaboré', '65334455'],
            ['Boulangerie La Ruche', '71445566'], ['Église Saint-Pierre', '74556677'], ['Adama Traoré', '60667788'],
            ['ONG Avenir Jeunes', '78778899'], ['Restaurant Le Baobab', '70889900'], ['Cabinet Zongo & Associés', '76990011'],
            ['Salimata Ouattara', '65001122'], ['Garage Excellence', '71102030'], ['Clinique Sainte Famille', '74203040'],
        ])->map(fn ($c, $i) => Client::create([
            'nom' => $c[0],
            'telephone' => $c[1],
            'email' => $i % 3 === 2 ? null : 'client'.($i + 1).'@example.com',
            'adresse' => ['Ouaga 2000', 'Gounghin', 'Dassasgho', 'Pissy', 'Tampouy', 'Patte d\'Oie'][$i % 6].', Ouagadougou',
        ]));

        $fournisseurs = collect([
            ['Papeterie Centrale du Faso', '25300011'], ['Import Bureau SARL', '25300022'],
            ['Textiles Wendkuuni', '25300033'], ['Encres & Consommables BF', '25300044'],
        ])->map(fn ($f) => Fournisseur::create(['nom' => $f[0], 'telephone' => $f[1], 'email' => null, 'adresse' => 'Zone commerciale, Ouagadougou']));

        return [$clients, $fournisseurs];
    }

    private function creerArticles($fournisseurs)
    {
        $catalogue = [
            // code, designation, prix achat, prix vente, stock initial, minimum, couleur
            ['A001', 'Cahier 200 pages', 450, 700, 300, 40, [37, 99, 235]],
            ['A002', 'Stylo bille bleu', 100, 200, 800, 100, [14, 116, 144]],
            ['A003', 'Rame papier A4', 2800, 3500, 120, 20, [100, 116, 139]],
            ['A004', 'Classeur A4', 700, 1000, 150, 20, [124, 58, 237]],
            ['A005', 'Agrafeuse', 1500, 2000, 40, 8, [71, 85, 105]],
            ['A006', 'Calculatrice', 1800, 2500, 35, 6, [5, 150, 105]],
            ['A007', 'T-shirt personnalisé', 2000, 3500, 90, 15, [234, 88, 12]],
            ['A008', 'Tasse imprimée', 1200, 2500, 60, 10, [219, 39, 119]],
            ['A009', 'Clé USB 32 Go', 3500, 5000, 30, 5, [15, 23, 42]],
            ['A010', 'Cartouche d\'encre', 6500, 9000, 25, 5, [8, 145, 178]],
            ['A011', 'Carnet de reçus', 600, 1000, 100, 15, [202, 138, 4]],
            ['A012', 'Enveloppe kraft (lot)', 900, 1500, 80, 10, [146, 64, 14]],
        ];

        return collect($catalogue)->map(function ($a, $i) use ($fournisseurs) {
            $article = Article::create([
                'code' => $a[0],
                'code_barre' => '61100'.str_pad((string) ($i + 1), 7, '0', STR_PAD_LEFT).'1',
                'designation' => $a[1],
                'photo_path' => $this->dessinerPhoto($a[1], $a[6]),
                'prix_achat' => $a[2],
                'prix_vente' => $a[3],
                'stock_initial' => $a[4],
                'stock_minimum' => $a[5],
                'stock_disponible' => $a[4],
            ]);

            // Le dernier achat de cartouches d'encre est a credit (dette fournisseur).
            $this->reapprovisionner($article, $fournisseurs, $i, $a[2], (int) round($a[4] * 0.35), $a[0] === 'A010');

            return $article;
        });
    }

    /**
     * Un achat de reapprovisionnement par mois de la periode. Si $dernierACredit, le
     * dernier est a credit avec un acompte (pour illustrer les dettes fournisseurs).
     */
    protected function reapprovisionner(Article $article, $fournisseurs, int $i, float $prixAchat, int $quantite, bool $dernierACredit = false): void
    {
        $nbMois = $this->nbMois();

        for ($m = 0; $m < $nbMois; $m++) {
            $date = $this->debut->addMonths($m)->addDays(2 + $i % 5);
            if ($date > CarbonImmutable::now()) {
                continue;
            }
            $credit = $dernierACredit && $m === $nbMois - 1;
            $total = $quantite * $prixAchat;
            $achat = Achat::create([
                'numero' => NumeroGenerator::suivant('achats', 'ACH', $this->entrepriseId),
                'date' => $date->toDateString(),
                'article_id' => $article->id,
                'fournisseur_id' => $fournisseurs[$i % $fournisseurs->count()]->id,
                'quantite' => $quantite,
                'prix_unitaire' => $prixAchat,
                'total' => $total,
                'mode_reglement' => $credit ? 'Credit' : 'Comptant',
                'montant_regle' => $credit ? round($total * 0.4) : $total,
            ]);
            $achat->rafraichirStatut();

            if ($credit) {
                ReglementFournisseur::create([
                    'numero' => NumeroGenerator::suivant('reglements_fournisseurs', 'REG', $this->entrepriseId),
                    'date' => min(CarbonImmutable::parse($achat->date)->addDays(10), CarbonImmutable::now())->toDateString(),
                    'fournisseur_id' => $achat->fournisseur_id, 'achat_id' => $achat->id,
                    'montant' => round($total * 0.2), 'mode_paiement' => 'Virement', 'observations' => 'Acompte',
                ]);
                // Le reglement rattache s'ajoute a ce qui est regle sur l'achat (comme dans l'application).
                $achat->montant_regle = round((float) $achat->montant_regle + round($total * 0.2), 2);
                $achat->rafraichirStatut();
            }
        }
    }

    /** Nombre de mois (entames) de la periode generee. */
    protected function nbMois(): int
    {
        return (int) $this->debut->diffInMonths(CarbonImmutable::now()->startOfMonth()) + 1;
    }

    protected function creerVentes($clients, $articles): void
    {
        $modes = ['Especes', 'Especes', 'Especes', 'Orange Money', 'Moov Money', 'Especes'];
        // Stock vendable (initial + achats) : comme dans l'application, on ne vend pas au-dela.
        $restant = $articles->mapWithKeys(fn ($a) => [$a->id => $a->stock_initial + (int) $a->achats()->sum('quantite') - (int) $a->venteLignes()->sum('quantite')])->all();
        $jour = $this->debut;
        $fin = CarbonImmutable::now();

        for (; $jour <= $fin; $jour = $jour->addDay()) {
            if ($jour->isSunday()) {
                continue;
            }
            // Activite en legere croissance au fil des mois
            $nbVentes = mt_rand(1, 3) + ($jour->diffInMonths($this->debut) >= 2 ? 1 : 0);

            for ($v = 0; $v < $nbVentes; $v++) {
                $client = $clients[mt_rand(0, $clients->count() - 1)];
                $quantites = [];
                foreach (collect(range(1, mt_rand(1, 3)))->map(fn () => $articles[mt_rand(0, $articles->count() - 1)])->unique('id') as $a) {
                    $q = min(mt_rand(1, $a->prix_vente < 1000 ? 12 : 3), $restant[$a->id] - 2);
                    if ($q > 0) {
                        $quantites[$a->id] = $q;
                        $restant[$a->id] -= $q;
                    }
                }
                if ($quantites === []) {
                    continue;
                }
                $lignes = $articles->whereIn('id', array_keys($quantites));
                $total = $lignes->sum(fn ($a) => $quantites[$a->id] * (float) $a->prix_vente);
                $aCredit = mt_rand(1, 100) <= 7 && $client->email; // quelques ventes a credit
                $mode = $modes[mt_rand(0, count($modes) - 1)];

                $vente = Vente::create([
                    'numero' => NumeroGenerator::suivant('ventes', 'VEN', $this->entrepriseId),
                    'date' => $jour->toDateString(),
                    'client_id' => $client->id,
                    'total' => $total,
                    'remise' => 0,
                    'remise_pourcentage' => 0,
                    'mode_paiement' => $mode,
                    'montant_remis' => $aCredit ? null : $total,
                ]);
                foreach ($lignes as $article) {
                    VenteLigne::create([
                        'vente_id' => $vente->id, 'article_id' => $article->id, 'quantite' => $quantites[$article->id],
                        'prix_unitaire' => $article->prix_vente, 'total' => $quantites[$article->id] * (float) $article->prix_vente,
                    ]);
                }
                PaiementVenteSynchroniseur::synchroniser($vente);
                JournalVenteSynchroniseur::synchroniser($vente->fresh());
            }
        }
    }

    protected function creerServicesEtDepenses(array $categories): void
    {
        $services = [
            'Infographie' => [['Logo et charte graphique', 25000, 3000], ['Affiche A2', 15000, 2500], ['Carte de visite (100 ex.)', 12000, 4500]],
            'Sérigraphie' => [['T-shirts équipe (20 ex.)', 60000, 32000], ['Casquettes brodées', 35000, 18000]],
            'Photographie' => [['Reportage mariage', 80000, 15000], ['Photos d\'identité', 6000, 1000]],
            'ABA' => [['Saisie et mise en page de mémoire', 20000, 2000]],
        ];
        $debut = $this->debut;

        for ($semaine = 0; $debut->addWeeks($semaine) <= CarbonImmutable::now(); $semaine++) {
            foreach ($services as $categorie => $prestations) {
                if (mt_rand(1, 100) > 70) {
                    continue;
                }
                [$libelle, $entree, $depense] = $prestations[mt_rand(0, count($prestations) - 1)];
                $date = $debut->addWeeks($semaine)->addDays(mt_rand(0, 5));
                if ($date > CarbonImmutable::now()) {
                    continue;
                }
                $moyen = mt_rand(0, 3) === 0 ? 'Mobile Money' : 'Especes';
                foreach ([['Depense', $depense], ['Entree', $entree]] as [$type, $montant]) {
                    EcritureJournal::create([
                        'date' => $date->toDateString(), 'categorie_activite_id' => $categories[$categorie]->id,
                        'type' => $type, 'description' => $libelle, 'montant' => $montant,
                        'moyen_paiement' => $moyen, 'compte' => CompteResolver::depuisMoyenPaiement($moyen),
                    ]);
                }
            }
        }

        // Charges fixes mensuelles
        for ($m = 0; $m < $this->nbMois(); $m++) {
            $mois = $debut->addMonths($m);
            foreach ([['Loyer de la boutique', 75000, 1], ['Électricité SONABEL', 32000, 8], ['Salaire vendeur', 60000, 28]] as [$libelle, $montant, $jourDuMois]) {
                $date = $mois->addDays($jourDuMois - 1);
                if ($date > CarbonImmutable::now()) {
                    continue;
                }
                EcritureJournal::create([
                    'date' => $date->toDateString(), 'categorie_activite_id' => $categories['Autres']->id,
                    'type' => 'Depense', 'description' => $libelle, 'montant' => $montant,
                    'moyen_paiement' => 'Especes', 'compte' => 'Caisse',
                ]);
            }
        }
    }

    protected function creerDocuments($clients, $fournisseurs): void
    {
        $prestations = [
            ['Impression de 500 brochures', 500, 350], ['Conception site vitrine', 1, 150000], ['Banderole 3 m', 2, 25000],
            ['Cartes de vœux personnalisées', 300, 400], ['Kakémono roll-up', 1, 45000], ['Badges PVC', 120, 750],
        ];
        // [jours avant aujourd'hui, part payee]
        $factures = [[95, 1.0], [70, 1.0], [52, 1.0], [40, 0.5], [33, 0.0], [21, 1.0], [12, 0.3], [5, 0.0]];

        foreach ($factures as $i => [$jours, $partPayee]) {
            $avecEmail = $clients->filter(fn ($c) => $c->email)->values();
            $client = $avecEmail->isNotEmpty() ? $avecEmail[$i % $avecEmail->count()] : $clients[$i % $clients->count()];
            [$designation, $quantite, $prix] = $prestations[$i % count($prestations)];
            $date = CarbonImmutable::now()->subDays($jours);
            $facture = Facture::create([
                'type' => Facture::TYPE_DEFINITIVE,
                'numero' => NumeroGenerator::suivantAvecSigle('factures_definitive', $this->sigle, $this->entrepriseId),
                'client_id' => $client->id, 'objet' => $designation, 'date' => $date->toDateString(),
                'montant_total' => $quantite * $prix, 'statut' => $partPayee >= 1 ? 'Payée' : 'Envoyée',
                'envoyee_le' => $date->addHours(2),
            ]);
            $facture->lignes()->create(['designation' => $designation, 'quantite' => $quantite, 'prix_unitaire' => $prix, 'total' => $quantite * $prix]);

            if ($partPayee > 0) {
                $paiement = PaiementClient::create([
                    'numero' => NumeroGenerator::suivant('paiements_clients', 'PAI', $this->entrepriseId),
                    'date' => $date->addDays(3)->toDateString(), 'client_id' => $client->id, 'facture_id' => $facture->id,
                    'montant' => round($quantite * $prix * $partPayee), 'mode_paiement' => $i % 2 ? 'Virement' : 'Especes',
                    'observations' => 'Règlement facture '.$facture->numero,
                ]);
                Recu::create([
                    'numero' => NumeroGenerator::suivant('recus', 'REC', $this->entrepriseId),
                    'paiement_client_id' => $paiement->id, 'date' => $paiement->date, 'montant' => $paiement->montant,
                ]);
            }

            if ($i === 2) {
                $bl = Facture::create([
                    'type' => Facture::TYPE_BORDEREAU, 'numero' => NumeroGenerator::suivant('factures_bordereau', 'BL', $this->entrepriseId),
                    'client_id' => $client->id, 'facture_liee_id' => $facture->id, 'objet' => $designation,
                    'date' => $date->addDay()->toDateString(), 'montant_total' => 0, 'statut' => 'Livré',
                ]);
                $bl->lignes()->create(['designation' => $designation, 'quantite' => $quantite, 'prix_unitaire' => 0, 'total' => 0, 'observations' => 'Livré en bon état']);
            }
        }

        // Proformas (devis)
        foreach ([[30, 'Acceptée'], [8, 'Envoyée'], [2, 'En attente']] as $j => [$jours, $statut]) {
            [$designation, $quantite, $prix] = $prestations[($j + 3) % count($prestations)];
            $proforma = Facture::create([
                'type' => Facture::TYPE_PROFORMA,
                'numero' => 'P-'.NumeroGenerator::suivantAvecSigle('factures_proforma', $this->sigle, $this->entrepriseId),
                'client_id' => $clients[($j + 5) % $clients->count()]->id, 'objet' => 'Devis : '.$designation,
                'date' => CarbonImmutable::now()->subDays($jours)->toDateString(), 'montant_total' => $quantite * $prix, 'statut' => $statut,
            ]);
            $proforma->lignes()->create(['designation' => $designation, 'quantite' => $quantite, 'prix_unitaire' => $prix, 'total' => $quantite * $prix]);
        }

        // Bon de commande fournisseur
        $bc = Facture::create([
            'type' => Facture::TYPE_BON_COMMANDE, 'numero' => NumeroGenerator::suivant('factures_bon_commande', 'BC', $this->entrepriseId),
            'fournisseur_id' => $fournisseurs[0]->id, 'objet' => 'Réassort rentrée scolaire',
            'date' => CarbonImmutable::now()->subDays(4)->toDateString(), 'montant_total' => 270000, 'remise_pourcentage' => 10, 'statut' => 'En attente',
        ]);
        $bc->lignes()->create(['designation' => 'Cahiers 200 pages (carton)', 'quantite' => 10, 'prix_unitaire' => 18000, 'total' => 180000]);
        $bc->lignes()->create(['designation' => 'Stylos bille (boîte de 50)', 'quantite' => 30, 'prix_unitaire' => 4000, 'total' => 120000]);
    }

    protected function creerDivers($clients): void
    {
        Acquisition::create(['date' => CarbonImmutable::now()->subMonths(2)->toDateString(), 'type' => 'Acquisition', 'designation' => 'Imprimante laser couleur', 'montant' => 285000, 'moyen_paiement' => 'Virement', 'compte' => 'Banque']);
        Acquisition::create(['date' => CarbonImmutable::now()->subMonth()->toDateString(), 'type' => 'Acquisition', 'designation' => 'Presse à chaud (sérigraphie)', 'montant' => 160000, 'moyen_paiement' => 'Especes', 'compte' => 'Caisse']);
        Acquisition::create(['date' => CarbonImmutable::now()->subDays(20)->toDateString(), 'type' => 'Depense_Reserve', 'designation' => 'Réparation climatiseur', 'montant' => 45000, 'moyen_paiement' => 'Especes', 'compte' => 'Caisse']);

        foreach ([['Uniformes brodés (30 pièces)', 30, 210000, 'En_cours', 6], ['Calendriers 2027', 200, 300000, 'En_attente', 15], ['Flyers A5 (1000 ex.)', 1000, 75000, 'Livree', -3]] as $i => [$designation, $quantite, $montant, $statut, $livraison]) {
            Commande::create([
                'numero' => NumeroGenerator::suivant('commandes', 'CMD', $this->entrepriseId),
                'client_id' => $clients[$i]->id, 'date' => CarbonImmutable::now()->subDays(10 - $i)->toDateString(),
                'designation' => $designation, 'quantite' => $quantite, 'montant' => $montant,
                'date_livraison_prevue' => CarbonImmutable::now()->addDays($livraison)->toDateString(), 'statut' => $statut,
            ]);
        }

        $vente = Vente::latest('id')->first();
        MonnaieARendre::create(['client_id' => $vente->client_id, 'vente_id' => $vente->id, 'date' => $vente->date, 'montant' => 500, 'statut' => 'due']);
    }

    protected function archiverMoisPasses(): void
    {
        $finances = new Finances();
        foreach ([3, 2] as $ilYa) {
            $mois = CarbonImmutable::now()->subMonths($ilYa)->startOfMonth();
            $resultat = $finances->resultat($mois, $mois->endOfMonth());
            ArchiveMensuelle::firstOrCreate(['mois' => $mois->toDateString()], [
                'nb_factures' => Facture::where('type', Facture::TYPE_DEFINITIVE)->whereBetween('date', [$mois->toDateString(), $mois->endOfMonth()->toDateString()])->count(),
                'chiffre_affaires' => $resultat['entrees'], 'depenses' => $resultat['depenses'], 'benefice' => $resultat['benefice'],
                'utilisateur_id' => auth()->id(),
            ]);
        }
    }

    // ---- Images generees (GD) : aucun fichier externe necessaire ----

    protected function police(bool $gras = true): string
    {
        return base_path('vendor/dompdf/dompdf/lib/fonts/'.($gras ? 'DejaVuSans-Bold.ttf' : 'DejaVuSans.ttf'));
    }

    protected function enregistrerImage($image, string $nom): string
    {
        $chemin = 'demo/'.$this->entrepriseId.'/'.$nom.'.png';
        ob_start();
        imagepng($image);
        Storage::disk('public')->put($chemin, ob_get_clean());
        imagedestroy($image);

        return $chemin;
    }

    protected function dessinerPhoto(string $designation, array $rgb): string
    {
        $taille = 320;
        $img = imagecreatetruecolor($taille, $taille);
        for ($y = 0; $y < $taille; $y++) {
            $t = $y / $taille;
            $c = imagecolorallocate($img, (int) ($rgb[0] + (255 - $rgb[0]) * $t * 0.35), (int) ($rgb[1] + (255 - $rgb[1]) * $t * 0.35), (int) ($rgb[2] + (255 - $rgb[2]) * $t * 0.35));
            imageline($img, 0, $y, $taille, $y, $c);
        }
        $blanc = imagecolorallocate($img, 255, 255, 255);
        $voile = imagecolorallocatealpha($img, 255, 255, 255, 100);
        imagefilledellipse($img, 250, 60, 180, 180, $voile);

        $initiales = mb_strtoupper(collect(preg_split('/\s+/', $designation))->filter()->map(fn ($m) => mb_substr($m, 0, 1))->take(2)->implode(''));
        $boite = imagettfbbox(96, 0, $this->police(), $initiales);
        imagettftext($img, 96, 0, (int) (($taille - ($boite[2] - $boite[0])) / 2), 190, $blanc, $this->police(), $initiales);

        $libelle = mb_strlen($designation) > 22 ? mb_substr($designation, 0, 21).'…' : $designation;
        $boite = imagettfbbox(17, 0, $this->police(false), $libelle);
        imagettftext($img, 17, 0, (int) (($taille - ($boite[2] - $boite[0])) / 2), 270, $blanc, $this->police(false), $libelle);

        return $this->enregistrerImage($img, 'article-'.substr(md5($designation), 0, 8));
    }

    private function dessinerLogo(): string
    {
        $img = imagecreatetruecolor(520, 200);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        $vert = imagecolorallocate($img, 5, 150, 105);
        $bleu = imagecolorallocate($img, 30, 58, 138);
        $gris = imagecolorallocate($img, 100, 116, 139);
        imagefilledellipse($img, 100, 100, 170, 170, $vert);
        imagettftext($img, 64, 0, 40, 128, imagecolorallocate($img, 255, 255, 255), $this->police(), 'ED');
        imagettftext($img, 40, 0, 205, 98, $bleu, $this->police(), 'Entreprise');
        imagettftext($img, 40, 0, 205, 150, $vert, $this->police(), 'Démo');
        imagettftext($img, 14, 0, 208, 182, $gris, $this->police(false), 'Services & Commerce');

        return $this->enregistrerImage($img, 'logo');
    }
}
