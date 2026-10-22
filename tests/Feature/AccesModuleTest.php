<?php

namespace Tests\Feature;

use App\Models\AccessRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_role_sans_droit_recoit_un_403(): void
    {
        $caissier = User::factory()->create(['profil' => 'Caissier', 'actif' => true]);

        AccessRule::create([
            'entreprise_id' => $caissier->entreprise_id,
            'module' => 'ARTICLES',
            'role' => 'Caissier',
            'autorise' => false,
        ]);

        $response = $this->actingAs($caissier)->get('/articles');

        $response->assertForbidden();
    }

    public function test_un_administrateur_a_toujours_acces(): void
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);

        $response = $this->actingAs($admin)->get('/articles');

        $response->assertOk();
    }
}
