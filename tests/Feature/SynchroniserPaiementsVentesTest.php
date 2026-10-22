<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Entreprise;
use App\Models\PaiementClient;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SynchroniserPaiementsVentesTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_commande_cree_les_paiements_manquants_pour_les_ventes_deja_reglees(): void
    {
        $entreprise = Entreprise::create(['nom' => 'Entreprise Test']);
        $client = Client::create(['entreprise_id' => $entreprise->id, 'nom' => 'Client Test']);

        $vente = Vente::create([
            'entreprise_id' => $entreprise->id,
            'numero' => 'VEN-2026-000001',
            'date' => now(),
            'client_id' => $client->id,
            'total' => 3000,
            'mode_paiement' => 'Especes',
            'montant_remis' => 3000,
        ]);

        $venteNonPayee = Vente::create([
            'entreprise_id' => $entreprise->id,
            'numero' => 'VEN-2026-000002',
            'date' => now(),
            'client_id' => $client->id,
            'total' => 1500,
        ]);

        $this->artisan('ventes:synchroniser-paiements')->assertSuccessful();

        $paiement = PaiementClient::where('vente_id', $vente->id)->firstOrFail();
        $this->assertEquals(3000, $paiement->montant);
        $this->assertEquals($client->id, $paiement->client_id);
        $this->assertEquals(0, PaiementClient::where('vente_id', $venteNonPayee->id)->count());
    }

    public function test_relancer_la_commande_ne_cree_pas_de_doublon(): void
    {
        $entreprise = Entreprise::create(['nom' => 'Entreprise Test']);
        $client = Client::create(['entreprise_id' => $entreprise->id, 'nom' => 'Client Test']);

        Vente::create([
            'entreprise_id' => $entreprise->id,
            'numero' => 'VEN-2026-000001',
            'date' => now(),
            'client_id' => $client->id,
            'total' => 3000,
            'mode_paiement' => 'Especes',
            'montant_remis' => 3000,
        ]);

        $this->artisan('ventes:synchroniser-paiements')->assertSuccessful();
        $this->artisan('ventes:synchroniser-paiements')->assertSuccessful();

        $this->assertEquals(1, PaiementClient::count());
    }
}
