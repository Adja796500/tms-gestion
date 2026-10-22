<?php

namespace Tests\Feature;

use App\Livewire\Articles;
use App\Models\Article;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ArticlePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_une_photo_peut_etre_ajoutee_puis_retiree(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);

        Livewire::test(Articles::class)
            ->call('creer')
            ->set('code', 'C010')
            ->set('designation', 'Tasse personnalisée')
            ->set('prix_vente', '3500')
            ->set('photo', UploadedFile::fake()->image('tasse.jpg', 400, 400))
            ->call('enregistrer')
            ->assertHasNoErrors();

        $article = Article::where('code', 'C010')->firstOrFail();
        $this->assertNotNull($article->photo_path);
        Storage::disk('public')->assertExists($article->photo_path);
        $this->get('/articles')->assertOk()->assertSee($article->photoUrl(), false);

        $ancienne = $article->photo_path;
        Livewire::test(Articles::class)
            ->call('modifier', $article->id)
            ->set('retirerPhoto', true)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertNull($article->fresh()->photo_path);
        Storage::disk('public')->assertMissing($ancienne);
    }

    public function test_la_recherche_ne_montre_pas_les_articles_d_une_autre_entreprise(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $autre = Entreprise::create(['nom' => 'Autre entreprise']);
        Article::withoutGlobalScopes()->create(['entreprise_id' => $autre->id, 'code' => 'X1', 'designation' => 'Cahier secret', 'prix_vente' => 1, 'stock_initial' => 0, 'stock_minimum' => 0, 'stock_disponible' => 0]);

        $this->actingAs($admin);
        Article::create(['code' => 'C1', 'designation' => 'Cahier', 'prix_vente' => 1, 'stock_initial' => 0, 'stock_minimum' => 0, 'stock_disponible' => 0]);

        Livewire::test(Articles::class)
            ->set('search', 'Cahier')
            ->assertSee('C1')
            ->assertDontSee('Cahier secret');
    }
}
