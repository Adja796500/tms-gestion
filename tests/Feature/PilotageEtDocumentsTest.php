<?php

namespace Tests\Feature;

use App\Livewire\Archives;
use App\Livewire\BonsCommande;
use App\Livewire\BordereauxLivraison;
use App\Livewire\MotDePasse;
use App\Livewire\Services;
use App\Livewire\Ventes;
use App\Models\Acquisition;
use App\Models\ArchiveMensuelle;
use App\Models\Article;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\PaiementClient;
use App\Models\ParametreEntreprise;
use App\Models\User;
use App\Support\Finances;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PilotageEtDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private function connecterAdmin(): User
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_la_tresorerie_compte_une_vente_payee_une_seule_fois(): void
    {
        $admin = $this->connecterAdmin();
        ParametreEntreprise::create(['entreprise_id' => $admin->entreprise_id, 'solde_initial_caisse' => 1000, 'taux_investissement' => 50, 'taux_epargne' => 10, 'taux_loyer' => 10, 'taux_fonctionnement' => 30]);
        $client = Client::create(['nom' => 'KONATE']);
        $article = Article::create(['code' => 'C1', 'designation' => 'Cahier', 'prix_vente' => 3000, 'stock_initial' => 10, 'stock_minimum' => 0, 'stock_disponible' => 10]);

        // Vente payee au comptoir : une ligne de journal (resultat) + un paiement client (tresorerie).
        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 2)
            ->set('lignes.0.prix_unitaire', 3000)
            ->set('montantRemis', '6000')
            ->call('enregistrer')
            ->assertHasNoErrors();

        // Service : depense de 500 en especes.
        Livewire::test(Services::class)
            ->set('designation', 'Encre')
            ->set('service', 'Sérigraphie')
            ->set('montantDepense', '500')
            ->call('enregistrer');

        $finances = new Finances();
        $this->assertSame(1000 + 6000 - 500.0, $finances->soldes()['Caisse']);

        $resultat = $finances->resultat();
        $this->assertEquals(6000, $resultat['entrees']);
        $this->assertEquals(500, $resultat['depenses']);
        $this->assertEquals(2750, $finances->repartition($resultat['benefice'])['investissement']);
    }

    public function test_les_pages_de_pilotage_s_affichent(): void
    {
        $this->connecterAdmin();
        Acquisition::create(['date' => now()->toDateString(), 'type' => 'Acquisition', 'designation' => 'Imprimante', 'montant' => 150000, 'moyen_paiement' => 'Especes', 'compte' => 'Caisse']);

        $this->get('/tresorerie')->assertOk()->assertSee('Solde de caisse');
        $this->get('/rapport-mensuel')->assertOk()->assertSee('Reste à investir');
        $this->get('/synthese')->assertOk()->assertSee('Synthèse mensuelle');
        $this->get('/statistiques')->assertOk()->assertSee('Imprimante');
        $this->get('/guide')->assertOk()->assertSee("Guide d'utilisation");
        $this->get('/audit')->assertOk();
    }

    public function test_un_bordereau_reprend_la_facture_liee_et_s_imprime(): void
    {
        $this->connecterAdmin();
        $client = Client::create(['nom' => 'KONATE']);
        $facture = Facture::create(['type' => Facture::TYPE_DEFINITIVE, 'numero' => '001/TMS/2026', 'client_id' => $client->id, 'date' => now()->toDateString(), 'montant_total' => 6000, 'statut' => 'Emise']);
        $facture->lignes()->create(['designation' => 'Cahier', 'quantite' => 2, 'prix_unitaire' => 3000, 'total' => 6000]);

        Livewire::test(BordereauxLivraison::class)
            ->call('creer')
            ->set('facture_liee_id', $facture->id)
            ->assertSet('partenaire_id', $client->id)
            ->assertSet('lignes.0.designation', 'Cahier')
            ->set('lignes.0.observations', 'Bon état')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $bordereau = Facture::where('type', Facture::TYPE_BORDEREAU)->firstOrFail();
        $this->assertStringStartsWith('BL-', $bordereau->numero);
        $this->assertSame($facture->id, $bordereau->facture_liee_id);

        $this->get(route('documents.imprimer', $bordereau))
            ->assertOk()
            ->assertSee('BORDEREAU DE LIVRAISON')
            ->assertSee('Bon état')
            ->assertSee('001/TMS/2026');
    }

    public function test_un_bon_de_commande_applique_la_remise(): void
    {
        $this->connecterAdmin();
        $fournisseur = Fournisseur::create(['nom' => 'YOUGMA']);

        Livewire::test(BonsCommande::class)
            ->call('creer')
            ->set('partenaire_id', $fournisseur->id)
            ->set('lignes.0.designation', 'Papier A4')
            ->set('lignes.0.quantite', 10)
            ->set('lignes.0.prix_unitaire', 2500)
            ->set('remise_pourcentage', '10')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $bon = Facture::where('type', Facture::TYPE_BON_COMMANDE)->firstOrFail();
        $this->assertStringStartsWith('BC-', $bon->numero);
        $this->assertEquals(22500, $bon->montant_total);
        $this->assertNull($bon->client_id);

        $this->get(route('documents.imprimer', $bon))->assertOk()->assertSee('BON DE COMMANDE')->assertSee('YOUGMA');
    }

    public function test_le_registre_calcule_le_reste_a_payer_d_une_facture(): void
    {
        $this->connecterAdmin();
        $client = Client::create(['nom' => 'KONATE']);
        $facture = Facture::create(['type' => Facture::TYPE_DEFINITIVE, 'numero' => '002/TMS/2026', 'client_id' => $client->id, 'date' => now()->toDateString(), 'montant_total' => 10000, 'statut' => 'Emise']);
        PaiementClient::create(['numero' => 'PAI-1', 'date' => now()->toDateString(), 'client_id' => $client->id, 'facture_id' => $facture->id, 'montant' => 4000, 'mode_paiement' => 'Especes']);

        $this->get('/registre-documents')
            ->assertOk()
            ->assertSee('002/TMS/2026')
            ->assertSee('Partiellement payée')
            ->assertSee('6 000');
    }

    public function test_archiver_un_mois_et_telecharger_les_exports(): void
    {
        $this->connecterAdmin();
        Livewire::test(Services::class)
            ->set('designation', 'Logo')
            ->set('service', 'Infographie')
            ->set('montantEntree', '25000')
            ->call('enregistrer');

        Livewire::test(Archives::class)
            ->set('moisAArchiver', now()->format('Y-m'))
            ->call('archiver')
            ->assertHasNoErrors();

        $archive = ArchiveMensuelle::firstOrFail();
        $this->assertEquals(25000, $archive->chiffre_affaires);

        $this->get(route('archives.exporter', now()->format('Y-m')))->assertOk()->assertHeader('Content-Type', 'application/zip');
        $this->get(route('sauvegarde.telecharger'))->assertOk()->assertHeader('Content-Type', 'application/zip');
    }

    public function test_un_caissier_n_accede_pas_a_la_tresorerie_ni_a_l_audit(): void
    {
        $caissier = User::factory()->create(['profil' => 'Caissier', 'actif' => true]);
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\AccessRuleSeeder']);

        $this->actingAs($caissier);
        $this->get('/tresorerie')->assertForbidden();
        $this->get('/audit')->assertForbidden();
    }

    public function test_changer_son_mot_de_passe(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true, 'password' => Hash::make('ancien-mdp')]);
        $this->actingAs($admin);

        Livewire::test(MotDePasse::class)
            ->set('actuel', 'mauvais')
            ->set('nouveau', 'nouveau-mdp')
            ->set('nouveau_confirmation', 'nouveau-mdp')
            ->call('enregistrer')
            ->assertHasErrors('actuel');

        Livewire::test(MotDePasse::class)
            ->set('actuel', 'ancien-mdp')
            ->set('nouveau', 'nouveau-mdp')
            ->set('nouveau_confirmation', 'nouveau-mdp')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('nouveau-mdp', $admin->fresh()->password));
    }
}
