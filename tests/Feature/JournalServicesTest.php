<?php

namespace Tests\Feature;

use App\Livewire\Comptabilite\EcrituresJournal;
use App\Livewire\HistoriqueVentes;
use App\Livewire\Services;
use App\Livewire\Ventes;
use App\Models\Article;
use App\Models\Client;
use App\Models\EcritureJournal;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JournalServicesTest extends TestCase
{
    use RefreshDatabase;

    private function connecterAdmin(): User
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);

        return $admin;
    }

    private function vendre(Client $client, Article $article, int $quantite): Vente
    {
        Livewire::test(Ventes::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.article_id', $article->id)
            ->set('lignes.0.quantite', $quantite)
            ->set('lignes.0.prix_unitaire', $article->prix_vente)
            ->call('enregistrer')
            ->assertHasNoErrors();

        return Vente::latest('id')->firstOrFail();
    }

    public function test_une_vente_ajoute_une_entree_au_journal(): void
    {
        $this->connecterAdmin();
        $client = Client::create(['nom' => 'KONATE']);
        $article = Article::create(['code' => 'C002', 'designation' => 'CAHIER', 'prix_vente' => 3000, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);

        $vente = $this->vendre($client, $article, 2);

        $ecriture = EcritureJournal::where('vente_id', $vente->id)->with('categorieActivite')->firstOrFail();
        $this->assertSame('Entree', $ecriture->type);
        $this->assertEquals(6000, $ecriture->montant);
        $this->assertSame('Vente', $ecriture->categorieActivite->nom);
        $this->assertSame('Vente - KONATE (1 article(s))', $ecriture->description);
        $this->assertSame('Caisse', $ecriture->compte);
    }

    public function test_supprimer_la_vente_supprime_sa_ligne_de_journal(): void
    {
        $this->connecterAdmin();
        $client = Client::create(['nom' => 'KONATE']);
        $article = Article::create(['code' => 'C002', 'designation' => 'CAHIER', 'prix_vente' => 3000, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);
        $vente = $this->vendre($client, $article, 1);

        Livewire::test(HistoriqueVentes::class)->call('supprimer', $vente->id);

        $this->assertSame(0, EcritureJournal::count());
    }

    public function test_les_lignes_de_vente_ne_se_modifient_pas_depuis_le_journal(): void
    {
        $this->connecterAdmin();
        $client = Client::create(['nom' => 'KONATE']);
        $article = Article::create(['code' => 'C002', 'designation' => 'CAHIER', 'prix_vente' => 3000, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);
        $vente = $this->vendre($client, $article, 1);
        $ecriture = EcritureJournal::where('vente_id', $vente->id)->firstOrFail();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(EcrituresJournal::class)->call('supprimer', $ecriture->id);
    }

    public function test_un_service_avec_entree_et_depense_cree_deux_ecritures(): void
    {
        $this->connecterAdmin();

        Livewire::test(Services::class)
            ->call('creer')
            ->set('designation', 'Affiche Adjara')
            ->set('service', 'Infographie')
            ->set('montantEntree', '10000')
            ->set('montantDepense', '1000')
            ->set('moyen_paiement', 'Especes')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertSame(2, EcritureJournal::count());
        $this->assertEquals(10000, EcritureJournal::where('type', 'Entree')->value('montant'));
        $this->assertEquals(1000, EcritureJournal::where('type', 'Depense')->value('montant'));
    }

    public function test_un_service_sans_montant_est_refuse(): void
    {
        $this->connecterAdmin();

        Livewire::test(Services::class)
            ->call('creer')
            ->set('designation', 'Logo')
            ->set('service', 'Infographie')
            ->call('enregistrer')
            ->assertHasErrors('montantEntree');

        $this->assertSame(0, EcritureJournal::count());
    }

    public function test_la_liste_des_services_exclut_les_ventes(): void
    {
        $this->connecterAdmin();
        $client = Client::create(['nom' => 'KONATE']);
        $article = Article::create(['code' => 'C002', 'designation' => 'CAHIER', 'prix_vente' => 3000, 'stock_initial' => 10, 'stock_minimum' => 1, 'stock_disponible' => 10]);
        $this->vendre($client, $article, 1);

        Livewire::test(Services::class)
            ->set('designation', 'Logo')
            ->set('service', 'Infographie')
            ->set('montantEntree', '25000')
            ->call('enregistrer');

        $this->get('/services')
            ->assertOk()
            ->assertSee('Logo')
            ->assertDontSee('Vente - KONATE');

        $this->get('/comptabilite/journal')
            ->assertOk()
            ->assertSee('Logo')
            ->assertSee('Vente - KONATE');
    }

    public function test_le_tableau_de_bord_affiche_les_sections_de_l_accueil(): void
    {
        $this->connecterAdmin();

        $this->get('/')
            ->assertOk()
            ->assertSee('Gestion des ventes')
            ->assertSee('Approvisionnement')
            ->assertSee(route('services.index'));
    }
}
