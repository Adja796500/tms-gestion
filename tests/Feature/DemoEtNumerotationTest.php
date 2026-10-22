<?php

namespace Tests\Feature;

use App\Livewire\FacturesDefinitives;
use App\Livewire\FacturesProforma;
use App\Models\Article;
use App\Models\Client;
use App\Models\Entreprise;
use App\Models\Facture;
use App\Models\ParametreEntreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DemoEtNumerotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_proforma_et_facture_ne_partagent_jamais_un_numero(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        ParametreEntreprise::create(['entreprise_id' => $admin->entreprise_id, 'sigle' => 'TMS']);
        $client = Client::create(['nom' => 'KONATE']);

        foreach ([FacturesProforma::class, FacturesDefinitives::class] as $composant) {
            Livewire::test($composant)
                ->call('creer')
                ->set('client_id', $client->id)
                ->set('lignes.0.designation', 'Affiche')
                ->set('lignes.0.quantite', 1)
                ->set('lignes.0.prix_unitaire', 1000)
                ->call('enregistrer')
                ->assertHasNoErrors();
        }

        $this->assertSame('P-001/TMS/'.now()->year, Facture::where('type', 'proforma')->value('numero'));
        $this->assertSame('001/TMS/'.now()->year, Facture::where('type', 'definitive')->value('numero'));
    }

    public function test_ajouter_des_exemples_ne_supprime_rien_et_respecte_le_stock(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $clientExistant = Client::create(['nom' => 'Client existant']);
        $article = Article::create(['code' => 'X1', 'designation' => 'Cahier', 'prix_vente' => 700, 'stock_initial' => 50, 'stock_minimum' => 5, 'stock_disponible' => 50]);
        auth()->forgetUser();

        $this->artisan('exemples:ajouter', ['entreprise' => (string) $admin->entreprise_id, '--mois' => 2])->assertSuccessful();

        $this->actingAs($admin);
        $this->assertTrue(Client::whereKey($clientExistant->id)->exists());
        $this->assertGreaterThan(0, \App\Models\Vente::count());
        $this->assertGreaterThanOrEqual(0, $article->fresh()->stock_disponible);
        $this->assertNotNull($article->fresh()->photo_path);
    }

    public function test_la_commande_de_demonstration_cree_une_entreprise_coherente(): void
    {
        Storage::fake('public'); // les images generees ne touchent pas au vrai dossier
        $this->artisan('demo:creer')->assertSuccessful();

        $entreprise = Entreprise::where('nom', 'Entreprise Démo')->firstOrFail();
        $admin = User::withoutGlobalScopes()->where('username', 'demo')->firstOrFail();
        $this->assertSame($entreprise->id, $admin->entreprise_id);

        $this->actingAs($admin);
        $this->assertSame(0, Article::where('stock_disponible', '<', 0)->count());
        $this->get('/')->assertOk()->assertSee('Entreprise Démo');
        $this->get('/relances')->assertOk();

        // Relancer la commande sans --reinitialiser ne duplique rien.
        $this->artisan('demo:creer')->assertFailed();
        $this->artisan('demo:creer', ['--reinitialiser' => true])->assertSuccessful();
        $this->assertSame(1, Entreprise::where('nom', 'Entreprise Démo')->count());
    }
}
