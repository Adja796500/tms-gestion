<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Facture;
use App\Models\FactureLigne;
use App\Models\ParametreEntreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactureImpressionTest extends TestCase
{
    use RefreshDatabase;

    private function creerFactureAvecLigne(Client $client, string $type = Facture::TYPE_PROFORMA, ?string $objet = null): Facture
    {
        $facture = Facture::create([
            'type' => $type,
            'numero' => 'PRO-2026-'.random_int(100000, 999999),
            'client_id' => $client->id,
            'objet' => $objet,
            'date' => now(),
            'montant_total' => 3500000,
            'statut' => 'Emise',
        ]);

        FactureLigne::create([
            'facture_id' => $facture->id,
            'designation' => 'Prestation',
            'quantite' => 1,
            'prix_unitaire' => 3500000,
            'total' => 3500000,
        ]);

        return $facture;
    }

    public function test_impression_affiche_l_objet_quand_il_est_renseigne(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);

        $facture = $this->creerFactureAvecLigne($client, Facture::TYPE_PROFORMA, 'Impression des flyers');

        $this->get(route('factures.imprimer', $facture))
            ->assertOk()
            ->assertSee('Objet :')
            ->assertSee('Impression des flyers');
    }

    public function test_impression_masque_l_objet_quand_il_est_vide(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);

        $facture = $this->creerFactureAvecLigne($client);

        $this->get(route('factures.imprimer', $facture))
            ->assertOk()
            ->assertDontSee('Objet :');
    }

    public function test_impression_affiche_le_montant_en_lettres(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);

        $facture = $this->creerFactureAvecLigne($client);

        $this->get(route('factures.imprimer', $facture))
            ->assertOk()
            ->assertSee('Trois millions cinq cent mille', false);
    }

    public function test_impression_affiche_le_logo_le_signataire_et_les_infos_legales(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);

        ParametreEntreprise::create([
            'entreprise_id' => $admin->entreprise_id,
            'nom_entreprise' => 'Rob Teegwende Multi-Services',
            'signataire' => 'Robert OUEDRAOGO',
            'rccm' => 'BF-OUA-2020-B-1234',
            'ifu' => '00012345A',
        ]);

        $facture = $this->creerFactureAvecLigne($client);

        $this->get(route('factures.imprimer', $facture))
            ->assertOk()
            ->assertSee('Rob Teegwende Multi-Services')
            ->assertSee('Robert OUEDRAOGO')
            ->assertSee('BF-OUA-2020-B-1234')
            ->assertSee('00012345A');
    }
}
