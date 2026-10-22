<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\NumeroGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumeroGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_deux_appels_successifs_donnent_des_numeros_consecutifs_et_uniques(): void
    {
        $utilisateur = User::factory()->create();

        $premier = NumeroGenerator::suivant('achats', 'ACH', $utilisateur->entreprise_id);
        $second = NumeroGenerator::suivant('achats', 'ACH', $utilisateur->entreprise_id);

        $annee = now()->year;

        $this->assertSame("ACH-{$annee}-000001", $premier);
        $this->assertSame("ACH-{$annee}-000002", $second);
        $this->assertNotSame($premier, $second);
    }

    public function test_une_autre_entreprise_repart_de_un(): void
    {
        $utilisateurA = User::factory()->create();
        $utilisateurB = User::factory()->create();

        $this->assertNotSame($utilisateurA->entreprise_id, $utilisateurB->entreprise_id);

        NumeroGenerator::suivant('achats', 'ACH', $utilisateurA->entreprise_id);
        NumeroGenerator::suivant('achats', 'ACH', $utilisateurA->entreprise_id);

        $premierPourB = NumeroGenerator::suivant('achats', 'ACH', $utilisateurB->entreprise_id);

        $annee = now()->year;

        $this->assertSame("ACH-{$annee}-000001", $premierPourB);
    }
}
