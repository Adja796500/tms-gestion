<?php

namespace Tests\Feature;

use App\Livewire\Articles;
use App\Livewire\Ventes;
use App\Models\Article;
use App\Models\Client;
use App\Models\Entreprise;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IsolationEntreprisesTest extends TestCase
{
    use RefreshDatabase;

    private function adminDe(string $nom): User
    {
        $entreprise = Entreprise::create(['nom' => $nom, 'actif' => true]);

        return User::factory()->create(['entreprise_id' => $entreprise->id, 'profil' => 'Administrateur', 'actif' => true]);
    }

    private function vendre(User $admin, string $codeArticle): void
    {
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client '.$admin->id]);
        $article = Article::create(['code' => $codeArticle, 'designation' => 'Cahier', 'prix_vente' => 500, 'stock_initial' => 10, 'stock_minimum' => 0, 'stock_disponible' => 10]);

        Livewire::test(Ventes::class)
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 1)
            ->set('lignes.0.prix_unitaire', 500)
            ->call('enregistrer')
            ->assertHasNoErrors();
    }

    public function test_deux_entreprises_ont_chacune_leur_numerotation_et_leurs_codes(): void
    {
        $this->vendre($this->adminDe('Entreprise A'), 'A001');
        $this->vendre($this->adminDe('Entreprise B'), 'A001'); // meme code article, meme numero de vente : autorise

        $numeros = Vente::withoutGlobalScopes()->pluck('numero');
        $this->assertCount(2, $numeros);
        $this->assertSame($numeros[0], $numeros[1]);
    }

    public function test_impossible_d_utiliser_le_client_d_une_autre_entreprise(): void
    {
        $adminA = $this->adminDe('Entreprise A');
        $this->actingAs($adminA);
        $clientA = Client::create(['nom' => 'Client secret de A']);

        $adminB = $this->adminDe('Entreprise B');
        $this->actingAs($adminB);
        $article = Article::create(['code' => 'B1', 'designation' => 'Stylo', 'prix_vente' => 200, 'stock_initial' => 5, 'stock_minimum' => 0, 'stock_disponible' => 5]);

        Livewire::test(Ventes::class)
            ->set('client_id', $clientA->id) // requete trafiquee
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', 1)
            ->set('lignes.0.prix_unitaire', 200)
            ->call('enregistrer')
            ->assertHasErrors('client_id');

        $this->assertSame(0, Vente::withoutGlobalScopes()->count());
    }

    public function test_un_code_article_deja_pris_dans_son_entreprise_est_refuse(): void
    {
        $this->actingAs($this->adminDe('Entreprise A'));
        Article::create(['code' => 'A001', 'designation' => 'Cahier', 'prix_vente' => 1, 'stock_initial' => 0, 'stock_minimum' => 0, 'stock_disponible' => 0]);

        Livewire::test(Articles::class)
            ->call('creer')
            ->set('code', 'A001')
            ->set('designation', 'Doublon')
            ->set('prix_vente', '100')
            ->call('enregistrer')
            ->assertHasErrors('code');
    }
}
