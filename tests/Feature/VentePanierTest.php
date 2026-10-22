<?php

namespace Tests\Feature;

use App\Livewire\HistoriqueVentes;
use App\Livewire\Ventes;
use App\Models\Article;
use App\Models\Client;
use App\Models\PaiementClient;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VentePanierTest extends TestCase
{
    use RefreshDatabase;

    public function test_une_vente_avec_plusieurs_articles_debite_le_stock_de_chacun(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $stylo = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);
        $cahier = Article::create(['code' => 'CAH', 'designation' => 'Cahier', 'prix_vente' => 500, 'stock_initial' => 5, 'stock_minimum' => 1, 'stock_disponible' => 5]);

        Livewire::actingAs($admin)
            ->test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $stylo->id)
            ->set('lignes.0.quantite', 4)
            ->set('lignes.0.prix_unitaire', 100)
            ->call('ajouterLigne')
            ->set('lignes.1.article_id', $cahier->id)
            ->set('lignes.1.quantite', 2)
            ->set('lignes.1.prix_unitaire', 500)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente = Vente::with('lignes')->firstOrFail();
        $this->assertEquals(1400, $vente->total);
        $this->assertCount(2, $vente->lignes);
        $this->assertEquals(6, $stylo->fresh()->stock_disponible);
        $this->assertEquals(3, $cahier->fresh()->stock_disponible);
    }

    public function test_la_vente_est_bloquee_si_la_quantite_depasse_le_stock_disponible(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 3, 'stock_minimum' => 1, 'stock_disponible' => 3]);

        Livewire::actingAs($admin)
            ->test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 5)
            ->set('lignes.0.prix_unitaire', 100)
            ->call('enregistrer')
            ->assertHasErrors('lignes.0.quantite');

        $this->assertEquals(0, Vente::count());
        $this->assertEquals(3, $article->fresh()->stock_disponible);
    }

    public function test_les_pages_ventes_et_tableau_de_bord_affichent_le_panier_sans_erreur(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $stylo = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);
        $cahier = Article::create(['code' => 'CAH', 'designation' => 'Cahier', 'prix_vente' => 500, 'stock_initial' => 5, 'stock_minimum' => 1, 'stock_disponible' => 5]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $stylo->id)
            ->set('lignes.0.quantite', 2)
            ->set('lignes.0.prix_unitaire', 100)
            ->call('ajouterLigne')
            ->set('lignes.1.article_id', $cahier->id)
            ->set('lignes.1.quantite', 1)
            ->set('lignes.1.prix_unitaire', 500)
            ->call('enregistrer');

        $this->get('/ventes')->assertOk();
        $this->get('/ventes/historique')->assertOk()->assertSee('Stylo')->assertSee('Cahier');
        $this->get('/')->assertOk();
    }

    public function test_un_client_et_un_article_inconnus_sont_enregistres_automatiquement_a_la_validation(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('clientRecherche', 'Nouveau Client SARL')
            ->set('lignes.0.recherche', 'Article cree depuis le panier')
            ->set('lignes.0.quantite', 3)
            ->set('lignes.0.prix_unitaire', 750)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $client = Client::where('nom', 'Nouveau Client SARL')->firstOrFail();
        $article = Article::where('designation', 'Article cree depuis le panier')->firstOrFail();
        $vente = Vente::with('lignes')->firstOrFail();

        $this->assertEquals($client->id, $vente->client_id);
        $this->assertEquals($article->id, $vente->lignes->first()->article_id);
        $this->assertEquals('ARTICL', $article->code);
        $this->assertEquals(750, $vente->lignes->first()->prix_unitaire);
        $this->assertEquals(2250, $vente->total);
        // stock_initial est cale sur la quantite vendue (3) pour que la vente ne soit
        // jamais bloquee par le controle de stock sur un article qui vient de naitre.
        $this->assertEquals(0, $article->fresh()->stock_disponible);
    }

    public function test_saisir_un_nom_de_client_ou_d_article_existant_le_resout_automatiquement(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Existant']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        $component = Livewire::test(Ventes::class)
            ->call('creer')
            ->set('clientRecherche', 'Client Existant')
            ->assertSet('client_id', $client->id)
            ->set('lignes.0.recherche', 'Stylo [STY]')
            ->assertSet('lignes.0.article_id', $article->id)
            ->assertSet('lignes.0.prix_unitaire', '100.00');

        $component->call('enregistrer')->assertHasNoErrors();

        $vente = Vente::firstOrFail();
        $this->assertEquals($client->id, $vente->client_id);
    }

    public function test_une_saisie_partielle_qui_ne_cible_plus_qu_un_seul_client_ou_article_est_resolue(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'OUEDRAOGO Robert']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo Bleu', 'prix_vente' => 150, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('clientRecherche', 'Robert')
            ->assertSet('client_id', $client->id)
            ->set('lignes.0.recherche', 'Stylo')
            ->assertSet('lignes.0.article_id', $article->id)
            ->assertSet('lignes.0.prix_unitaire', '150.00');
    }

    public function test_une_saisie_partielle_ambigue_ne_resout_rien_et_bloque_l_enregistrement(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        Client::create(['nom' => 'OUEDRAOGO Robert']);
        Client::create(['nom' => 'Robert Sawadogo']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        $component = Livewire::test(Ventes::class)
            ->call('creer')
            ->set('clientRecherche', 'Robert')
            ->assertSet('client_id', null);

        $this->assertCount(2, $component->instance()->clientsCorrespondants());

        $component->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 1)
            ->set('lignes.0.prix_unitaire', 100)
            ->call('enregistrer')
            ->assertHasErrors('client_id');

        $this->assertEquals(0, Vente::count());
        $this->assertEquals(2, Client::count());
    }

    public function test_valider_le_panier_declenche_l_impression_du_recu_de_vente(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 2)
            ->set('lignes.0.prix_unitaire', 100)
            ->call('enregistrer')
            ->assertDispatched('vente-enregistree');

        $vente = Vente::firstOrFail();

        $this->get("/ventes/{$vente->id}/imprimer")
            ->assertOk()
            ->assertSee($vente->numero)
            ->assertSee('Stylo')
            ->assertSee('Client Test');
    }

    public function test_la_remise_en_pourcentage_reduit_le_total_de_la_vente(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 5)
            ->set('lignes.0.prix_unitaire', 100)
            ->set('remisePourcentage', 40)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente = Vente::firstOrFail();
        $this->assertEquals(40, $vente->remise_pourcentage);
        $this->assertEquals(200, $vente->remise);
        $this->assertEquals(300, $vente->total);
    }

    public function test_une_remise_superieure_a_100_pourcent_est_rejetee(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 1)
            ->set('lignes.0.prix_unitaire', 100)
            ->set('remisePourcentage', 150)
            ->call('enregistrer')
            ->assertHasErrors('remisePourcentage');

        $this->assertEquals(0, Vente::count());
    }

    public function test_on_peut_modifier_une_vente_existante_en_changeant_l_article_et_la_quantite(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $savon = Article::create(['code' => 'SAV', 'designation' => 'Savon', 'prix_vente' => 500, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);
        $lotion = Article::create(['code' => 'LOT', 'designation' => 'Lotion', 'prix_vente' => 1000, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $savon->id)
            ->set('lignes.0.quantite', 2)
            ->set('lignes.0.prix_unitaire', 500)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente = Vente::with('lignes')->firstOrFail();
        $this->assertEquals(8, $savon->fresh()->stock_disponible);

        // Le client change d'avis : il ne veut plus le savon, il veut de la lotion.
        Livewire::test(Ventes::class)
            ->call('modifier', $vente->id)
            ->assertSet('client_id', $client->id)
            ->set('lignes.0.article_id', $lotion->id)
            ->set('lignes.0.recherche', "Lotion [LOT]")
            ->set('lignes.0.quantite', 1)
            ->set('lignes.0.prix_unitaire', 1000)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente->refresh();
        $vente->load('lignes');

        $this->assertCount(1, $vente->lignes);
        $this->assertEquals($lotion->id, $vente->lignes->first()->article_id);
        $this->assertEquals(1000, $vente->total);
        // Le stock du savon rendu doit etre restitue, celui de la lotion debite.
        $this->assertEquals(10, $savon->fresh()->stock_disponible);
        $this->assertEquals(9, $lotion->fresh()->stock_disponible);
    }

    public function test_modifier_une_vente_sans_toucher_a_la_quantite_ne_bloque_pas_sur_le_stock(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        // Stock tout juste suffisant pour la vente initiale : la reouvrir pour
        // simplement corriger la remise ne doit pas se bloquer sur son propre stock.
        $article = Article::create(['code' => 'SAV', 'designation' => 'Savon', 'prix_vente' => 500, 'stock_initial' => 3, 'stock_minimum' => 1, 'stock_disponible' => 3]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 3)
            ->set('lignes.0.prix_unitaire', 500)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente = Vente::firstOrFail();

        Livewire::test(Ventes::class)
            ->call('modifier', $vente->id)
            ->set('remisePourcentage', 10)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente->refresh();
        $this->assertEquals(1350, $vente->total);
        $this->assertEquals(0, $article->fresh()->stock_disponible);
    }

    public function test_modifier_une_vente_repopule_le_pourcentage_de_remise_dans_le_panier(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 4)
            ->set('lignes.0.prix_unitaire', 100)
            ->set('remisePourcentage', 25)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente = Vente::firstOrFail();

        Livewire::test(Ventes::class)
            ->call('modifier', $vente->id)
            ->assertSet('remisePourcentage', '25.00');
    }

    public function test_visiter_ventes_avec_le_parametre_modifier_ouvre_directement_le_panier_de_cette_vente(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client A Modifier']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        $vente = Vente::create([
            'numero' => 'VEN-2026-TEST01',
            'date' => now(),
            'client_id' => $client->id,
            'total' => 100,
        ]);
        \App\Models\VenteLigne::create([
            'vente_id' => $vente->id,
            'article_id' => $article->id,
            'quantite' => 1,
            'prix_unitaire' => 100,
            'total' => 100,
        ]);

        $this->get("/ventes?modifier={$vente->id}")
            ->assertOk()
            ->assertSee('Modifier la vente')
            ->assertSee('Client A Modifier');
    }

    public function test_le_mode_de_paiement_espece_avec_monnaie_calcule_le_rendu(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 3)
            ->set('lignes.0.prix_unitaire', 100)
            ->set('modePaiement', 'Especes')
            ->set('montantRemis', 500)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente = Vente::firstOrFail();
        $this->assertEquals('Especes', $vente->mode_paiement);
        $this->assertEquals(500, $vente->montant_remis);
        $this->assertEquals(300, $vente->total);
    }

    public function test_un_montant_remis_insuffisant_est_rejete(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 3)
            ->set('lignes.0.prix_unitaire', 100)
            ->set('modePaiement', 'Especes')
            ->set('montantRemis', 200)
            ->call('enregistrer')
            ->assertHasErrors('montantRemis');

        $this->assertEquals(0, Vente::count());
    }

    public function test_orange_money_est_un_mode_de_paiement_valide_sans_monnaie(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 1)
            ->set('lignes.0.prix_unitaire', 100)
            ->set('modePaiement', 'Orange Money')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente = Vente::firstOrFail();
        $this->assertEquals('Orange Money', $vente->mode_paiement);
        $this->assertNull($vente->montant_remis);
    }

    public function test_la_monnaie_non_rendue_est_enregistree_dans_le_registre(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 3)
            ->set('lignes.0.prix_unitaire', 100)
            ->set('modePaiement', 'Especes')
            ->set('montantRemis', 500)
            ->set('monnaieNonRendue', true)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente = Vente::firstOrFail();
        $monnaie = \App\Models\MonnaieARendre::where('vente_id', $vente->id)->firstOrFail();

        $this->assertEquals($client->id, $monnaie->client_id);
        $this->assertEquals(200, $monnaie->montant);
        $this->assertEquals('due', $monnaie->statut);
    }

    public function test_une_vente_payee_au_comptoir_cree_un_paiement_client(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 3)
            ->set('lignes.0.prix_unitaire', 100)
            ->set('modePaiement', 'Especes')
            ->set('montantRemis', 500)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente = Vente::firstOrFail();
        $paiement = PaiementClient::where('vente_id', $vente->id)->firstOrFail();

        $this->assertEquals($client->id, $paiement->client_id);
        $this->assertEquals(300, $paiement->montant);
        $this->assertEquals('Especes', $paiement->mode_paiement);
        $this->assertEquals(300.0, $client->fresh()->totalPaye());
        $this->assertEquals(0.0, $client->fresh()->resteAPayer());
    }

    public function test_une_vente_sans_paiement_ne_cree_aucun_paiement_client(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 3)
            ->set('lignes.0.prix_unitaire', 100)
            ->set('modePaiement', 'Orange Money')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente = Vente::firstOrFail();

        $this->assertEquals(0, PaiementClient::where('vente_id', $vente->id)->count());
        $this->assertEquals(300.0, $client->fresh()->resteAPayer());
    }

    public function test_retirer_le_paiement_d_une_vente_modifiee_supprime_le_paiement_client(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 3)
            ->set('lignes.0.prix_unitaire', 100)
            ->set('modePaiement', 'Especes')
            ->set('montantRemis', 500)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente = Vente::firstOrFail();
        $this->assertEquals(1, PaiementClient::where('vente_id', $vente->id)->count());

        Livewire::test(Ventes::class)
            ->call('modifier', $vente->id)
            ->set('montantRemis', '')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertEquals(0, PaiementClient::where('vente_id', $vente->id)->count());
    }

    public function test_supprimer_une_vente_payee_supprime_le_paiement_client_lie(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 3)
            ->set('lignes.0.prix_unitaire', 100)
            ->set('modePaiement', 'Especes')
            ->set('montantRemis', 500)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente = Vente::firstOrFail();
        $this->assertEquals(1, PaiementClient::where('vente_id', $vente->id)->count());

        Livewire::test(HistoriqueVentes::class)->call('supprimer', $vente->id);

        $this->assertEquals(0, Vente::count());
        $this->assertEquals(0, PaiementClient::count());
    }
}
