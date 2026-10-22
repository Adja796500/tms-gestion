<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\Achat;
use App\Models\User;
use App\Models\Vente;
use App\Models\VenteLigne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_stock_disponible_tient_compte_des_achats_et_des_ventes(): void
    {
        $this->actingAs(User::factory()->create());

        $article = Article::create([
            'code' => 'T001',
            'designation' => 'Test',
            'prix_vente' => 1000,
            'stock_initial' => 10,
            'stock_minimum' => 2,
            'stock_disponible' => 10,
        ]);

        $fournisseur = Fournisseur::create(['nom' => 'Fournisseur Test']);
        Achat::create([
            'numero' => 'ACH-2026-000001',
            'date' => now(),
            'article_id' => $article->id,
            'fournisseur_id' => $fournisseur->id,
            'quantite' => 5,
            'prix_unitaire' => 500,
            'total' => 2500,
            'mode_reglement' => 'Comptant',
            'montant_regle' => 2500,
        ]);

        $client = Client::create(['nom' => 'Client Test']);
        $vente = Vente::create([
            'numero' => 'VEN-2026-000001',
            'date' => now(),
            'client_id' => $client->id,
            'total' => 3000,
        ]);
        VenteLigne::create([
            'vente_id' => $vente->id,
            'article_id' => $article->id,
            'quantite' => 3,
            'prix_unitaire' => 1000,
            'total' => 3000,
        ]);

        $article->recalculerStock();

        $this->assertEquals(12, $article->fresh()->stock_disponible); // 10 + 5 - 3
    }
}
