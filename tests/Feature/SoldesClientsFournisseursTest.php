<?php

namespace Tests\Feature;

use App\Livewire\ReglementsFournisseurs;
use App\Models\Achat;
use App\Models\Article;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\PaiementClient;
use App\Models\ReglementFournisseur;
use App\Models\User;
use App\Models\Vente;
use App\Support\Finances;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SoldesClientsFournisseursTest extends TestCase
{
    use RefreshDatabase;

    private function connecterAdmin(): User
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);

        return $admin;
    }

    private function achat(Fournisseur $fournisseur, float $total, float $regle): Achat
    {
        $article = Article::firstOrCreate(['code' => 'P1'], ['designation' => 'Papier', 'prix_vente' => 100, 'stock_initial' => 0, 'stock_minimum' => 0, 'stock_disponible' => 0]);
        $achat = Achat::create(['numero' => uniqid('ACH'), 'date' => now()->toDateString(), 'article_id' => $article->id, 'fournisseur_id' => $fournisseur->id,
            'quantite' => 1, 'prix_unitaire' => $total, 'total' => $total, 'mode_reglement' => $regle >= $total ? 'Comptant' : 'Credit', 'montant_regle' => $regle]);
        $achat->rafraichirStatut();

        return $achat;
    }

    public function test_un_paiement_de_facture_ne_rend_pas_la_creance_negative(): void
    {
        $this->connecterAdmin();
        $client = Client::create(['nom' => 'KONATE']);
        Vente::create(['numero' => 'V1', 'date' => now()->toDateString(), 'client_id' => $client->id, 'total' => 5000, 'remise' => 0, 'remise_pourcentage' => 0, 'mode_paiement' => 'Especes']);
        $facture = Facture::create(['type' => 'definitive', 'numero' => 'F1', 'client_id' => $client->id, 'date' => now()->toDateString(), 'montant_total' => 20000, 'statut' => 'Emise']);
        PaiementClient::create(['numero' => 'P1', 'date' => now()->toDateString(), 'client_id' => $client->id, 'facture_id' => $facture->id, 'montant' => 20000, 'mode_paiement' => 'Especes']);

        // Doit encore la vente (5 000), la facture est soldee.
        $this->assertEquals(5000, $client->resteAPayer());
        $this->get('/creances-clients')->assertOk()->assertSee('5 000');
        $this->get('/')->assertOk()->assertDontSee('-15 000');
    }

    public function test_un_achat_paye_comptant_n_est_pas_une_dette(): void
    {
        $this->connecterAdmin();
        $fournisseur = Fournisseur::create(['nom' => 'Papeterie']);
        $this->achat($fournisseur, 150000, 150000);           // comptant
        $credit = $this->achat($fournisseur, 100000, 40000);  // credit : 60 000 dus

        $this->get('/dettes-fournisseurs')->assertOk()->assertSee('60 000');

        // Un reglement rattache a l'achat a credit : compte une seule fois en tresorerie.
        Livewire::test(ReglementsFournisseurs::class)
            ->call('creer')
            ->set('fournisseur_id', $fournisseur->id)
            ->set('achat_id', $credit->id)
            ->set('montant', '20000')
            ->set('mode_paiement', 'Especes')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->get('/dettes-fournisseurs')->assertSee('40 000');
        // Sorties : 150 000 + 40 000 (a l'achat) + 20 000 (reglement) = 210 000.
        $this->assertEquals(210000, (new Finances())->flux()['Caisse']['sorties']);
    }

    public function test_supprimer_un_reglement_retire_son_montant_de_l_achat(): void
    {
        $this->connecterAdmin();
        $fournisseur = Fournisseur::create(['nom' => 'Papeterie']);
        $achat = $this->achat($fournisseur, 100000, 40000);

        $composant = Livewire::test(ReglementsFournisseurs::class)
            ->call('creer')
            ->set('fournisseur_id', $fournisseur->id)
            ->set('achat_id', $achat->id)
            ->set('montant', '60000')
            ->set('mode_paiement', 'Especes')
            ->call('enregistrer');
        $this->assertEquals(100000, $achat->fresh()->montant_regle);

        $composant->call('supprimer', ReglementFournisseur::firstOrFail()->id);

        $this->assertEquals(40000, $achat->fresh()->montant_regle);
        $this->assertSame('Partiellement regle', $achat->fresh()->statut);
    }

    public function test_un_reglement_ne_peut_pas_depasser_le_reste_ni_viser_un_autre_fournisseur(): void
    {
        $this->connecterAdmin();
        $fournisseur = Fournisseur::create(['nom' => 'Papeterie']);
        $autre = Fournisseur::create(['nom' => 'Autre']);
        $paye = $this->achat($fournisseur, 150000, 150000);

        Livewire::test(ReglementsFournisseurs::class)
            ->call('creer')
            ->set('fournisseur_id', $fournisseur->id)
            ->set('achat_id', $paye->id)
            ->set('montant', '1000000')
            ->set('mode_paiement', 'Especes')
            ->call('enregistrer')
            ->assertHasErrors('montant')
            ->set('fournisseur_id', $autre->id)
            ->set('montant', '1000')
            ->call('enregistrer')
            ->assertHasErrors('achat_id');

        $this->assertSame(0, ReglementFournisseur::count());
    }
}
