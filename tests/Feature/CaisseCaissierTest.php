<?php

namespace Tests\Feature;

use App\Livewire\HistoriqueVentes;
use App\Livewire\Ventes;
use App\Models\Article;
use App\Models\Client;
use App\Models\User;
use App\Models\Vente;
use Database\Seeders\AccessRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CaisseCaissierTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateur(string $profil): User
    {
        $user = User::factory()->create(['profil' => $profil, 'actif' => true]);
        (new AccessRuleSeeder())->run($user->entreprise_id); // droits du classeur (DROITS_ACCES)

        return $user;
    }

    private function article(string $code = 'C1', string $designation = 'Cahier', int $prix = 700): Article
    {
        return Article::create(['code' => $code, 'designation' => $designation, 'prix_vente' => $prix, 'stock_initial' => 20, 'stock_minimum' => 0, 'stock_disponible' => 20]);
    }

    public function test_le_caissier_encaisse_une_vente_au_comptoir(): void
    {
        $this->actingAs($this->utilisateur('Caissier'));
        $client = Client::create(['nom' => 'KONATE']);
        $this->article();

        $this->get('/ventes')->assertOk();

        Livewire::test(Ventes::class)
            ->set('client_id', $client->id)
            ->set('codeScan', 'C1')->call('scanner')
            ->set('montantRemis', '1000')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $vente = Vente::firstOrFail();
        $this->assertEquals(700, $vente->total);
        $this->get(route('ventes.imprimer', $vente))->assertOk(); // le reçu
        $this->get('/monnaies-a-rendre')->assertOk();
    }

    public function test_le_client_revient_changer_un_produit_et_en_ajouter_un(): void
    {
        $this->actingAs($this->utilisateur('Caissier'));
        $client = Client::create(['nom' => 'KONATE']);
        $cahier = $this->article('C1', 'Cahier', 700);
        $stylo = $this->article('S1', 'Stylo', 200);
        $classeur = $this->article('K1', 'Classeur', 1000);

        Livewire::test(Ventes::class)
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $cahier->id)->set('lignes.0.quantite', 1)->set('lignes.0.prix_unitaire', 700)
            ->call('enregistrer')->assertHasNoErrors();
        $vente = Vente::firstOrFail();

        // L'historique est accessible au caissier, avec le bouton Modifier.
        $this->get('/ventes/historique')->assertOk()->assertSee('Modifier');

        // Le client remplace le cahier par un classeur et ajoute un stylo.
        Livewire::withQueryParams(['modifier' => $vente->id])->test(Ventes::class)
            ->set('lignes.0.article_id', $classeur->id)->set('lignes.0.prix_unitaire', 1000)
            ->call('ajouterLigne')
            ->set('lignes.1.article_id', $stylo->id)->set('lignes.1.quantite', 1)->set('lignes.1.prix_unitaire', 200)
            ->call('enregistrer')->assertHasNoErrors();

        $this->assertSame(1, Vente::count());
        $this->assertEquals(1200, $vente->fresh()->total);
        $this->assertEquals(20, $cahier->fresh()->stock_disponible);   // le cahier est revenu en stock
        $this->assertEquals(19, $classeur->fresh()->stock_disponible);
        $this->assertEquals(19, $stylo->fresh()->stock_disponible);
    }

    public function test_le_caissier_ne_supprime_pas_une_vente(): void
    {
        $this->actingAs($this->utilisateur('Caissier'));
        $client = Client::create(['nom' => 'KONATE']);
        $vente = Vente::create(['numero' => 'V1', 'date' => now()->toDateString(), 'client_id' => $client->id, 'total' => 700, 'remise' => 0, 'remise_pourcentage' => 0, 'mode_paiement' => 'Especes']);

        Livewire::test(HistoriqueVentes::class)->call('supprimer', $vente->id)->assertForbidden();
        $this->assertSame(1, Vente::count());
    }

    public function test_le_caissier_cree_un_article_a_la_volee(): void
    {
        $this->actingAs($this->utilisateur('Caissier'));
        $client = Client::create(['nom' => 'KONATE']);

        Livewire::test(Ventes::class)
            ->set('client_id', $client->id)
            ->set('lignes.0.recherche', 'Gomme blanche')
            ->set('lignes.0.quantite', 2)
            ->set('lignes.0.prix_unitaire', 150)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $article = Article::where('designation', 'Gomme blanche')->firstOrFail();
        $this->assertEquals(150, $article->prix_vente);
        $this->assertSame(1, Vente::count());
    }

    public function test_le_magasinier_cree_un_client_a_la_volee(): void
    {
        $this->actingAs($this->utilisateur('Magasinier'));
        $article = $this->article();

        Livewire::test(Ventes::class)
            ->set('clientRecherche', 'Nouveau client pressé')
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 1)
            ->set('lignes.0.prix_unitaire', 700)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertTrue(Client::where('nom', 'Nouveau client pressé')->exists());
    }
}
