<?php

namespace Tests\Feature;

use App\Livewire\HistoriqueVentes;
use App\Models\Article;
use App\Models\Client;
use App\Models\User;
use App\Models\Vente;
use App\Models\VenteLigne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HistoriqueVentesTest extends TestCase
{
    use RefreshDatabase;

    private function creerVenteAvecLigne(Client $client, Article $article, int $quantite = 2): Vente
    {
        $vente = Vente::create([
            'numero' => 'VEN-2026-'.random_int(100000, 999999),
            'date' => now(),
            'client_id' => $client->id,
            'total' => $quantite * $article->prix_vente,
        ]);

        VenteLigne::create([
            'vente_id' => $vente->id,
            'article_id' => $article->id,
            'quantite' => $quantite,
            'prix_unitaire' => $article->prix_vente,
            'total' => $quantite * $article->prix_vente,
        ]);

        $article->recalculerStock();

        return $vente;
    }

    public function test_la_recherche_filtre_par_numero_ou_client(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $clientA = Client::create(['nom' => 'Alpha Client']);
        $clientB = Client::create(['nom' => 'Beta Client']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        $this->creerVenteAvecLigne($clientA, $article);
        $this->creerVenteAvecLigne($clientB, $article);

        Livewire::test(HistoriqueVentes::class)
            ->set('search', 'Alpha')
            ->assertSee('Alpha Client')
            ->assertDontSee('Beta Client');
    }

    public function test_supprimer_une_vente_restitue_le_stock(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $article = Article::create(['code' => 'STY', 'designation' => 'Stylo', 'prix_vente' => 100, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        $vente = $this->creerVenteAvecLigne($client, $article, 4);
        $this->assertEquals(6, $article->fresh()->stock_disponible);

        Livewire::test(HistoriqueVentes::class)
            ->call('supprimer', $vente->id);

        $this->assertEquals(0, Vente::count());
        $this->assertEquals(10, $article->fresh()->stock_disponible);
    }

    public function test_la_page_historique_est_accessible_et_affiche_le_menu(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);

        $this->get('/ventes/historique')
            ->assertOk()
            ->assertSee('Historique des ventes');
    }
}
