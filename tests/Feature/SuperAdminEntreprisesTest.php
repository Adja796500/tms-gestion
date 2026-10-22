<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Entreprises;
use App\Models\CategorieActivite;
use App\Models\Entreprise;
use App\Models\ParametreEntreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminEntreprisesTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $sa = User::factory()->create(['entreprise_id' => null, 'profil' => 'Super Administrateur', 'est_super_administrateur' => true, 'actif' => true]);
        $this->actingAs($sa);

        return $sa;
    }

    public function test_creer_une_entreprise_prepare_sa_fiche_et_son_administrateur(): void
    {
        $this->superAdmin();

        Livewire::test(Entreprises::class)
            ->call('creer')
            ->set('nomEntreprise', 'Boutique Wend-Kuuni')
            ->set('adminName', 'Awa Sawadogo')
            ->set('adminUsername', 'awa')
            ->set('adminPassword', 'Bienvenue2026')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $entreprise = Entreprise::where('nom', 'Boutique Wend-Kuuni')->firstOrFail();
        $admin = User::withoutGlobalScopes()->where('username', 'awa')->firstOrFail();
        $this->assertSame($entreprise->id, $admin->entreprise_id);
        $this->assertSame('Administrateur', $admin->profil);

        $fiche = ParametreEntreprise::withoutGlobalScopes()->where('entreprise_id', $entreprise->id)->firstOrFail();
        $this->assertSame('Boutique Wend-Kuuni', $fiche->nom_entreprise);
        $this->assertNull($fiche->logo_path);
        $this->assertEquals(55, $fiche->taux_investissement);
        $this->assertSame(7, CategorieActivite::withoutGlobalScopes()->where('entreprise_id', $entreprise->id)->count());
    }

    public function test_une_entreprise_sans_logo_n_affiche_pas_celui_d_une_autre(): void
    {
        $tms = Entreprise::create(['nom' => 'Rob Teegwende', 'actif' => true]);
        ParametreEntreprise::withoutGlobalScopes()->create(['entreprise_id' => $tms->id, 'logo_path' => 'logos/tms.png']);
        $autre = Entreprise::create(['nom' => 'Boutique Wend Kuuni', 'actif' => true]);
        $admin = User::factory()->create(['entreprise_id' => $autre->id, 'profil' => 'Administrateur', 'actif' => true]);

        $this->actingAs($admin)
            ->get('/mon-role')
            ->assertOk()
            ->assertDontSee('logos/tms.png')
            ->assertDontSee('/images/logo-marque.png')
            ->assertSee('BW'); // initiales de l'entreprise
    }

    public function test_renommer_et_reinitialiser_un_mot_de_passe(): void
    {
        $this->superAdmin();
        $entreprise = Entreprise::create(['nom' => 'ENTREPRISE DAME', 'actif' => true]);
        $admin = User::factory()->create(['entreprise_id' => $entreprise->id, 'username' => 'admin2', 'profil' => 'Administrateur', 'actif' => true]);

        Livewire::test(Entreprises::class)
            ->call('ouvrirRenommage', $entreprise->id)
            ->set('nouveauNom', 'Rob Teegwende Multi-Services')
            ->call('renommer')
            ->assertHasNoErrors()
            ->call('ouvrirMotDePasse', $entreprise->id)
            ->assertSet('utilisateurId', $admin->id)
            ->set('nouveauMotDePasse', 'court')
            ->call('reinitialiserMotDePasse')
            ->assertHasErrors('nouveauMotDePasse')
            ->set('nouveauMotDePasse', 'NouveauMdp2026')
            ->call('reinitialiserMotDePasse')
            ->assertHasNoErrors();

        $this->assertSame('Rob Teegwende Multi-Services', $entreprise->fresh()->nom);
        $this->assertTrue(Hash::check('NouveauMdp2026', $admin->fresh()->password));
    }

    public function test_le_super_administrateur_est_renvoye_hors_des_pages_des_entreprises(): void
    {
        $this->superAdmin();

        foreach (['/configuration-entreprise', '/', '/ventes', '/relances', '/clients'] as $page) {
            $this->get($page)->assertRedirect(route('super-admin.entreprises'));
        }

        $this->get(route('super-admin.entreprises'))->assertOk();
        $this->get('/mot-de-passe')->assertOk();
        $this->assertSame(0, ParametreEntreprise::withoutGlobalScopes()->whereNull('entreprise_id')->count());
    }

    public function test_le_logo_de_la_connexion_vient_de_la_configuration(): void
    {
        config(['plateforme.logo' => '/images/autre-logo.png']);

        $this->get('/login')->assertOk()->assertSee('/images/autre-logo.png');
    }
}
