<?php

namespace Tests\Feature;

use App\Livewire\MonnaiesARendre;
use App\Models\Client;
use App\Models\MonnaieARendre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MonnaiesARendreTest extends TestCase
{
    use RefreshDatabase;

    public function test_marquer_une_monnaie_comme_reglee(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client Test']);
        $entree = MonnaieARendre::create([
            'client_id' => $client->id,
            'date' => now(),
            'montant' => 150,
            'statut' => 'due',
        ]);

        Livewire::test(MonnaiesARendre::class)
            ->call('marquerReglee', $entree->id)
            ->assertDispatched('monnaie-reglee');

        $entree->refresh();
        $this->assertEquals('reglee', $entree->statut);
        $this->assertNotNull($entree->reglee_le);
    }

    public function test_le_justificatif_s_imprime_et_reflete_le_statut(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);
        $client = Client::create(['nom' => 'Client A Imprimer']);
        $entree = MonnaieARendre::create([
            'client_id' => $client->id,
            'date' => now(),
            'montant' => 250,
            'statut' => 'due',
        ]);

        $this->get("/monnaies-a-rendre/{$entree->id}/imprimer")
            ->assertOk()
            ->assertSee('Client A Imprimer')
            ->assertSee('Monnaie à rendre');

        $entree->update(['statut' => 'reglee', 'reglee_le' => now()]);

        $this->get("/monnaies-a-rendre/{$entree->id}/imprimer")
            ->assertOk()
            ->assertSee('Monnaie rendue');
    }

    public function test_la_page_est_accessible(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);

        $this->get('/monnaies-a-rendre')
            ->assertOk()
            ->assertSee('Monnaie à rendre');
    }
}
