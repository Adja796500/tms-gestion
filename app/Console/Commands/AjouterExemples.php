<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\CategorieActivite;
use App\Models\Client;
use App\Models\Entreprise;
use App\Models\Fournisseur;
use App\Models\ParametreEntreprise;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * AJOUTE une activite d'illustration (ventes, services, achats, factures payees ou en
 * retard, commandes...) a une entreprise existante, en reutilisant ses articles et ses
 * clients. Rien n'est supprime ni modifie, sauf : les articles sans photo recoivent une
 * vignette generee (retirable depuis la fiche article).
 */
class AjouterExemples extends CreerDemo
{
    protected $signature = 'exemples:ajouter
        {entreprise : Nom ou numéro de l\'entreprise}
        {--mois=3 : Nombre de mois d\'activité à générer, jusqu\'à aujourd\'hui}';

    protected $description = 'Ajoute des données d\'exemple cohérentes à une entreprise existante (sans rien supprimer)';

    public function handle(): int
    {
        $critere = $this->argument('entreprise');
        $entreprise = ctype_digit($critere) ? Entreprise::find($critere) : Entreprise::where('nom', $critere)->first();

        if (! $entreprise) {
            $this->error("Entreprise « {$critere} » introuvable.");

            return self::FAILURE;
        }

        $admin = User::withoutGlobalScopes()->where('entreprise_id', $entreprise->id)->where('profil', 'Administrateur')->first();
        if (! $admin) {
            $this->error("Cette entreprise n'a pas de compte Administrateur.");

            return self::FAILURE;
        }

        // Tout est cree "au nom" de son administrateur : cloisonnement automatique.
        auth()->forgetUser();
        auth()->setUser($admin);
        $this->entrepriseId = $entreprise->id;
        $this->sigle = ParametreEntreprise::where('entreprise_id', $entreprise->id)->value('sigle') ?: 'FAC';
        $this->debut = CarbonImmutable::now()->subMonths(max(1, (int) $this->option('mois')) - 1)->startOfMonth();
        mt_srand(crc32($entreprise->nom));

        $avant = $this->compter();

        DB::transaction(function () {
            $categories = collect(['ABA', 'Photographie', 'Infographie', 'Caligraphie', 'Sérigraphie', 'Vente', 'Autres'])
                ->mapWithKeys(fn ($nom) => [$nom => CategorieActivite::firstOrCreate(['nom' => $nom], ['actif' => true])])
                ->all();

            $clients = $this->completerClients();
            $fournisseurs = $this->completerFournisseurs();
            $articles = Article::orderBy('id')->get();

            if ($articles->isEmpty()) {
                throw new \RuntimeException("L'entreprise n'a aucun article : créez au moins quelques articles avant d'ajouter des exemples.");
            }

            $creditFait = false;
            foreach ($articles as $i => $article) {
                if (! $article->photo_path) {
                    $teinte = [[37, 99, 235], [5, 150, 105], [234, 88, 12], [124, 58, 237], [8, 145, 178], [219, 39, 119], [202, 138, 4]][crc32($article->code) % 7];
                    $article->update(['photo_path' => $this->dessinerPhoto($article->designation, $teinte)]);
                }

                $prixAchat = (float) ($article->prix_achat ?: round((float) $article->prix_vente * 0.7));
                $quantite = (float) $article->prix_vente < 1000 ? 60 : ((float) $article->prix_vente < 5000 ? 20 : 8);
                $aCredit = ! $creditFait && (float) $article->prix_vente >= 2000;
                $creditFait = $creditFait || $aCredit;
                $this->reapprovisionner($article, $fournisseurs, $i, $prixAchat, $quantite, $aCredit);
            }

            $this->creerVentes($clients, $articles);
            $this->creerServicesEtDepenses($categories);
            $this->creerDocuments($clients, $fournisseurs);
            $this->creerDivers($clients);

            foreach ($articles as $article) {
                $article->recalculerStock();
            }
            $this->archiverMoisPasses();
        });

        $apres = $this->compter();
        $this->info("Exemples ajoutés à « {$entreprise->nom} » (".$this->debut->format('d/m/Y').' → aujourd\'hui) :');
        $this->table(['', 'Avant', 'Après'], collect($avant)->map(fn ($n, $k) => [$k, $n, $apres[$k]])->values()->all());

        return self::SUCCESS;
    }

    private function completerClients()
    {
        $nouveaux = [
            ['Lycée Wend-Manegda', '70112233'], ['Pharmacie du Progrès', '76223344'], ['Boulangerie La Ruche', '71445566'],
            ['ONG Avenir Jeunes', '78778899'], ['Restaurant Le Baobab', '70889900'], ['Cabinet Zongo & Associés', '76990011'],
            ['Garage Excellence', '71102030'], ['Clinique Sainte Famille', '74203040'],
        ];
        foreach ($nouveaux as $i => [$nom, $telephone]) {
            Client::firstOrCreate(['nom' => $nom], [
                'telephone' => $telephone,
                'email' => 'client'.($i + 1).'@example.com',
                'adresse' => ['Ouaga 2000', 'Gounghin', 'Dassasgho', 'Pissy'][$i % 4].', Ouagadougou',
            ]);
        }

        return Client::orderBy('id')->get();
    }

    private function completerFournisseurs()
    {
        foreach ([['Papeterie Centrale du Faso', '25300011'], ['Encres & Consommables BF', '25300044']] as [$nom, $telephone]) {
            Fournisseur::firstOrCreate(['nom' => $nom], ['telephone' => $telephone, 'adresse' => 'Zone commerciale, Ouagadougou']);
        }

        return Fournisseur::orderBy('id')->get();
    }

    /**
     * @return array<string, int>
     */
    private function compter(): array
    {
        return [
            'Ventes' => \App\Models\Vente::count(),
            'Achats' => \App\Models\Achat::count(),
            'Écritures du journal' => \App\Models\EcritureJournal::count(),
            'Factures / documents' => \App\Models\Facture::count(),
            'Paiements clients' => \App\Models\PaiementClient::count(),
            'Clients' => Client::count(),
        ];
    }
}
