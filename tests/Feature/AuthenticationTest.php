<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_actif_peut_se_connecter(): void
    {
        $user = User::factory()->create([
            'username' => 'testeur',
            'password' => bcrypt('MotDePasse123'),
            'profil' => 'Administrateur',
            'actif' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'testeur',
            'password' => 'MotDePasse123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
    }

    public function test_un_utilisateur_inactif_ne_peut_pas_se_connecter(): void
    {
        User::factory()->create([
            'username' => 'inactif',
            'password' => bcrypt('MotDePasse123'),
            'profil' => 'Consultation',
            'actif' => false,
        ]);

        $response = $this->post('/login', [
            'username' => 'inactif',
            'password' => 'MotDePasse123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }
}
