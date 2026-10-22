<?php

namespace Tests\Feature;

use App\Livewire\BordereauxLivraison;
use App\Livewire\Comptabilite\EcrituresJournal;
use App\Livewire\FacturesProforma;
use App\Livewire\RegistreDocuments;
use App\Models\Client;
use App\Models\EcritureJournal;
use App\Models\Facture;
use App\Models\PaiementClient;
use App\Models\Recu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FluxProfessionnelsTest extends TestCase
{
    use RefreshDatabase;

    private function connecterAdmin(): User
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);

        return $admin;
    }

    private function facture(Client $client, string $type = Facture::TYPE_DEFINITIVE, float $montant = 10000): Facture
    {
        $facture = Facture::create(['type' => $type, 'numero' => uniqid('DOC'), 'client_id' => $client->id, 'objet' => 'Affiches', 'date' => now()->toDateString(), 'montant_total' => $montant, 'statut' => 'Emise']);
        $facture->lignes()->create(['designation' => 'Affiche A3', 'quantite' => 4, 'prix_unitaire' => $montant / 4, 'total' => $montant]);

        return $facture;
    }

    public function test_convertir_une_proforma_en_facture(): void
    {
        $this->connecterAdmin();
        $proforma = $this->facture(Client::create(['nom' => 'KONATE']), Facture::TYPE_PROFORMA);

        Livewire::test(FacturesProforma::class)->call('convertirEnFacture', $proforma->id);

        $facture = Facture::where('type', Facture::TYPE_DEFINITIVE)->with('lignes')->firstOrFail();
        $this->assertEquals(10000, $facture->montant_total);
        $this->assertSame('Affiche A3', $facture->lignes->first()->designation);
        $this->assertSame('Acceptée', $proforma->fresh()->statut);
    }

    public function test_le_bordereau_s_ouvre_prerempli_depuis_une_facture(): void
    {
        $this->connecterAdmin();
        $client = Client::create(['nom' => 'KONATE']);
        $facture = $this->facture($client);

        Livewire::withQueryParams(['facture' => $facture->id])
            ->test(BordereauxLivraison::class)
            ->assertSet('showForm', true)
            ->assertSet('partenaire_id', $client->id)
            ->assertSet('lignes.0.designation', 'Affiche A3');
    }

    public function test_encaisser_une_facture_depuis_le_registre_la_solde_et_genere_le_recu(): void
    {
        $this->connecterAdmin();
        $facture = $this->facture(Client::create(['nom' => 'KONATE', 'telephone' => '60 45 68 84']));

        Livewire::test(RegistreDocuments::class)
            ->call('ouvrirEncaissement', $facture->id)
            ->assertSet('encaissementMontant', '10000')
            ->set('encaissementMontant', '15000')
            ->call('encaisser')
            ->assertHasErrors('encaissementMontant')
            ->set('encaissementMontant', '10000')
            ->call('encaisser')
            ->assertHasNoErrors();

        $this->assertEquals(10000, PaiementClient::where('facture_id', $facture->id)->sum('montant'));
        $this->assertSame('Payée', $facture->fresh()->statut);
        $this->assertSame(1, Recu::count());
    }

    public function test_le_registre_propose_whatsapp_avec_le_numero_du_client(): void
    {
        $this->connecterAdmin();
        $this->facture(Client::create(['nom' => 'KONATE', 'telephone' => '60 45 68 84']));

        $this->get('/registre-documents')->assertOk()->assertSee('https://wa.me/22660456884', false);
    }

    public function test_le_journal_filtre_et_exporte(): void
    {
        $this->connecterAdmin();
        EcritureJournal::create(['date' => now()->toDateString(), 'type' => 'Entree', 'description' => 'Logo client', 'montant' => 25000, 'moyen_paiement' => 'Especes', 'compte' => 'Caisse']);
        EcritureJournal::create(['date' => now()->toDateString(), 'type' => 'Depense', 'description' => 'Encre', 'montant' => 5000, 'moyen_paiement' => 'Virement', 'compte' => 'Banque']);

        Livewire::test(EcrituresJournal::class)
            ->set('filtreCompte', 'Caisse')
            ->assertViewHas('totalEntrees', 25000.0)
            ->assertViewHas('totalDepenses', 0.0)
            ->assertSee('Logo client')
            ->assertDontSee('Encre');

        $reponse = $this->get(route('comptabilite.journal.exporter', ['type' => 'Depense']));
        $reponse->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Encre', $reponse->getContent());
        $this->assertStringNotContainsString('Logo client', $reponse->getContent());
    }

    public function test_le_tableau_de_bord_affiche_graphiques_et_points_a_traiter(): void
    {
        $this->connecterAdmin();
        $this->facture(Client::create(['nom' => 'KONATE']));

        $this->get('/')
            ->assertOk()
            ->assertSee('Entrées et dépenses — 12 derniers mois')
            ->assertSee('1 facture(s) non soldée(s)');
    }
}
