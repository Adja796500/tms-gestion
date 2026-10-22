<?php

namespace Tests\Feature;

use App\Livewire\FacturesDefinitives;
use App\Livewire\FacturesProforma;
use App\Models\Article;
use App\Models\Client;
use App\Models\Facture;
use App\Models\PaiementClient;
use App\Models\ParametreEntreprise;
use App\Models\User;
use App\Models\Vente;
use App\Models\VenteLigne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FactureFormulaireTest extends TestCase
{
    use RefreshDatabase;

    public function test_saisir_un_nom_de_client_existant_le_resout_automatiquement(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'SCOOP-CA APEC']);

        Livewire::test(FacturesProforma::class)
            ->call('creer')
            ->set('clientRecherche', 'SCOOP-CA APEC')
            ->assertSet('client_id', $client->id)
            ->set('lignes.0.designation', 'Impression des flyers')
            ->set('lignes.0.quantite', 2000)
            ->set('lignes.0.prix_unitaire', 1750)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $facture = Facture::firstOrFail();
        $this->assertEquals($client->id, $facture->client_id);
    }

    public function test_un_client_inconnu_est_enregistre_automatiquement_a_la_validation(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);

        Livewire::test(FacturesDefinitives::class)
            ->call('creer')
            ->set('clientRecherche', 'Nouveau Client SARL')
            ->set('lignes.0.designation', 'Prestation')
            ->set('lignes.0.quantite', 1)
            ->set('lignes.0.prix_unitaire', 5000)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $client = Client::where('nom', 'Nouveau Client SARL')->firstOrFail();
        $facture = Facture::firstOrFail();
        $this->assertEquals($client->id, $facture->client_id);
    }

    public function test_une_saisie_ambigue_bloque_l_enregistrement(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        Client::create(['nom' => 'OUEDRAOGO Robert']);
        Client::create(['nom' => 'Robert Sawadogo']);

        Livewire::test(FacturesProforma::class)
            ->call('creer')
            ->set('clientRecherche', 'Robert')
            ->assertSet('client_id', null)
            ->set('lignes.0.designation', 'Prestation')
            ->set('lignes.0.quantite', 1)
            ->set('lignes.0.prix_unitaire', 5000)
            ->call('enregistrer')
            ->assertHasErrors('client_id');
    }

    public function test_le_numero_utilise_le_sigle_configure_avec_repli_sur_le_prefixe(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);

        ParametreEntreprise::create([
            'entreprise_id' => $admin->entreprise_id,
            'sigle' => 'TMS',
        ]);

        Livewire::test(FacturesProforma::class)
            ->call('creer')
            ->set('clientRecherche', $client->nom)
            ->set('lignes.0.designation', 'Prestation')
            ->set('lignes.0.quantite', 1)
            ->set('lignes.0.prix_unitaire', 5000)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $facture = Facture::firstOrFail();
        $this->assertMatchesRegularExpression('#^P-\d{3}/TMS/\d{4}$#', $facture->numero);
    }

    public function test_le_numero_utilise_le_prefixe_par_defaut_sans_sigle_configure(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);

        Livewire::test(FacturesDefinitives::class)
            ->call('creer')
            ->set('clientRecherche', $client->nom)
            ->set('lignes.0.designation', 'Prestation')
            ->set('lignes.0.quantite', 1)
            ->set('lignes.0.prix_unitaire', 5000)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $facture = Facture::firstOrFail();
        $this->assertMatchesRegularExpression('#^\d{3}/FAC/\d{4}$#', $facture->numero);
    }

    public function test_selectionner_un_client_affiche_ses_coordonnees_son_solde_et_ses_dernieres_factures(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Solde', 'telephone' => '70 00 00 00']);

        $vente = Vente::create(['numero' => 'VEN-2026-000001', 'date' => now(), 'client_id' => $client->id, 'total' => 10000]);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo Bleu', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);
        VenteLigne::create(['vente_id' => $vente->id, 'article_id' => $article->id, 'quantite' => 10, 'prix_unitaire' => 100, 'total' => 1000]);
        PaiementClient::create(['numero' => 'PAI-2026-000001', 'date' => now(), 'client_id' => $client->id, 'montant' => 4000, 'mode_paiement' => 'Especes']);

        $ancienneFacture = Facture::create([
            'type' => Facture::TYPE_PROFORMA,
            'numero' => '001/PRO/2026',
            'client_id' => $client->id,
            'date' => now(),
            'montant_total' => 5000,
            'statut' => 'Emise',
        ]);

        $component = Livewire::test(FacturesProforma::class)
            ->call('creer')
            ->set('clientRecherche', 'Client Solde')
            ->assertSet('client_id', $client->id)
            ->assertSee('70 00 00 00')
            ->assertSee('001/PRO/2026');

        $infos = $component->instance()->clientInfos();

        $this->assertEquals(4000.0, $infos['totalPaye']);
        $this->assertEquals(6000.0, $infos['resteAPayer']);
        $this->assertTrue($infos['dernieresFactures']->contains('id', $ancienneFacture->id));
    }

    public function test_selectionner_un_client_pre_remplit_les_lignes_avec_ses_derniers_achats(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Achats']);

        $vente = Vente::create(['numero' => 'VEN-2026-000002', 'date' => now(), 'client_id' => $client->id, 'total' => 1000]);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo Bleu', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);
        VenteLigne::create(['vente_id' => $vente->id, 'article_id' => $article->id, 'quantite' => 10, 'prix_unitaire' => 100, 'total' => 1000]);

        Livewire::test(FacturesProforma::class)
            ->call('creer')
            ->call('selectionnerClient', $client->id)
            ->assertSet('lignes.0.designation', 'Stylo Bleu')
            ->assertSet('lignes.0.quantite', 10)
            ->assertSet('lignes.0.prix_unitaire', '100.00');
    }

    public function test_le_pre_remplissage_n_ecrase_pas_une_ligne_deja_saisie(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Achats']);

        $vente = Vente::create(['numero' => 'VEN-2026-000003', 'date' => now(), 'client_id' => $client->id, 'total' => 1000]);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo Bleu', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);
        VenteLigne::create(['vente_id' => $vente->id, 'article_id' => $article->id, 'quantite' => 10, 'prix_unitaire' => 100, 'total' => 1000]);

        Livewire::test(FacturesProforma::class)
            ->call('creer')
            ->set('lignes.0.designation', 'Saisie manuelle deja en cours')
            ->call('selectionnerClient', $client->id)
            ->assertSet('lignes.0.designation', 'Saisie manuelle deja en cours');
    }

    public function test_la_fiche_client_n_apparait_pas_tant_qu_aucun_client_n_est_selectionne(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);

        Livewire::test(FacturesProforma::class)
            ->call('creer')
            ->assertSet('client_id', null)
            ->assertDontSee('Deja paye');
    }
}
