<?php

namespace Tests\Feature;

use App\Livewire\Articles;
use App\Models\Achat;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleLivraisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_creer_un_article_avec_un_fournisseur_inconnu_cree_le_fournisseur_et_l_achat(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);

        Livewire::test(Articles::class)
            ->call('creer')
            ->set('code', 'NEW01')
            ->set('designation', 'Nouvel article')
            ->set('prix_vente', 1000)
            ->set('livraisons.0.fournisseurRecherche', 'Grossiste Ouaga')
            ->set('livraisons.0.date', '2026-09-01')
            ->set('livraisons.0.quantite', 25)
            ->set('livraisons.0.prix_achat', 600)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $article = Article::where('code', 'NEW01')->firstOrFail();
        $fournisseur = Fournisseur::where('nom', 'Grossiste Ouaga')->firstOrFail();
        $achat = Achat::where('article_id', $article->id)->firstOrFail();

        $this->assertEquals($fournisseur->id, $achat->fournisseur_id);
        $this->assertEquals(25, $achat->quantite);
        $this->assertEquals(600, $achat->prix_unitaire);
        $this->assertEquals('Comptant', $achat->mode_reglement);
        $this->assertEquals(25, $article->fresh()->stock_disponible);
    }

    public function test_un_meme_article_peut_avoir_plusieurs_fournisseurs(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $fournisseurA = Fournisseur::create(['nom' => 'Fournisseur A']);
        $fournisseurB = Fournisseur::create(['nom' => 'Fournisseur B']);

        Livewire::test(Articles::class)
            ->call('creer')
            ->set('code', 'NEW02')
            ->set('designation', 'Article multi-fournisseurs')
            ->set('prix_vente', 500)
            ->set('livraisons.0.fournisseur_id', $fournisseurA->id)
            ->set('livraisons.0.fournisseurRecherche', 'Fournisseur A')
            ->set('livraisons.0.quantite', 10)
            ->set('livraisons.0.prix_achat', 300)
            ->call('ajouterLivraison')
            ->set('livraisons.1.fournisseur_id', $fournisseurB->id)
            ->set('livraisons.1.fournisseurRecherche', 'Fournisseur B')
            ->set('livraisons.1.quantite', 15)
            ->set('livraisons.1.prix_achat', 320)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $article = Article::where('code', 'NEW02')->firstOrFail();
        $this->assertEquals(2, Achat::where('article_id', $article->id)->count());
        $this->assertEquals(25, $article->fresh()->stock_disponible);
    }

    public function test_modifier_un_article_existant_n_affiche_pas_ni_ne_traite_les_livraisons(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $article = Article::create(['code' => 'EXI01', 'designation' => 'Existant', 'prix_vente' => 100, 'stock_initial' => 5, 'stock_minimum' => 1, 'stock_disponible' => 5]);

        Livewire::test(Articles::class)
            ->call('modifier', $article->id)
            ->assertSet('livraisons', [])
            ->set('designation', 'Existant modifie')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertEquals(0, Achat::count());
        $this->assertEquals('Existant modifie', $article->fresh()->designation);
    }
}
