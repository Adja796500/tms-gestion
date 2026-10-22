<?php

namespace Tests\Feature;

use App\Livewire\FacturesDefinitives;
use App\Livewire\RegistreDocuments;
use App\Livewire\RelancesClients;
use App\Livewire\Ventes;
use App\Mail\DocumentMail;
use App\Models\Article;
use App\Models\Client;
use App\Models\Facture;
use App\Models\ParametreEntreprise;
use App\Models\User;
use App\Support\Sauvegarde;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AutomatisationsTest extends TestCase
{
    use RefreshDatabase;

    private function connecterAdmin(): User
    {
        $admin = User::factory()->create(['profil' => 'Administrateur', 'actif' => true]);
        $this->actingAs($admin);

        return $admin;
    }

    private function facture(Client $client, int $joursRetard = 0, float $montant = 10000): Facture
    {
        $facture = Facture::create(['type' => Facture::TYPE_DEFINITIVE, 'numero' => uniqid('FAC'), 'client_id' => $client->id, 'date' => now()->subDays($joursRetard)->toDateString(), 'montant_total' => $montant, 'statut' => 'Emise']);
        $facture->lignes()->create(['designation' => 'Affiche A3', 'quantite' => 2, 'prix_unitaire' => $montant / 2, 'total' => $montant]);

        return $facture;
    }

    public function test_le_pdf_d_une_facture_est_genere(): void
    {
        $this->connecterAdmin();
        $facture = $this->facture(Client::create(['nom' => 'KONATE']));

        $reponse = $this->get(route('documents.pdf', $facture));

        $reponse->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $reponse->getContent());
    }

    public function test_envoyer_une_facture_par_email_depuis_le_registre(): void
    {
        Mail::fake();
        $this->connecterAdmin();
        $facture = $this->facture(Client::create(['nom' => 'KONATE', 'email' => 'konate@example.com']));

        Livewire::test(RegistreDocuments::class)->call('envoyerParEmail', $facture->id)->assertHasNoErrors();

        Mail::assertSent(DocumentMail::class, fn (DocumentMail $m) => $m->hasTo('konate@example.com') && ! $m->relance && count($m->attachments()) === 1);
        $this->assertNotNull($facture->fresh()->envoyee_le);
    }

    public function test_envoi_automatique_a_la_creation_si_active(): void
    {
        Mail::fake();
        $admin = $this->connecterAdmin();
        ParametreEntreprise::create(['entreprise_id' => $admin->entreprise_id, 'envoi_auto_factures' => true]);
        $client = Client::create(['nom' => 'KONATE', 'email' => 'konate@example.com']);

        Livewire::test(FacturesDefinitives::class)
            ->call('creer')
            ->set('client_id', $client->id)
            ->set('lignes.0.designation', 'Logo')
            ->set('lignes.0.quantite', 1)
            ->set('lignes.0.prix_unitaire', 25000)
            ->call('enregistrer')
            ->assertHasNoErrors();

        Mail::assertSent(DocumentMail::class, 1);
    }

    public function test_les_relances_automatiques_respectent_delai_et_intervalle(): void
    {
        Mail::fake();
        $admin = $this->connecterAdmin();
        ParametreEntreprise::create(['entreprise_id' => $admin->entreprise_id, 'relances_actives' => true, 'relance_delai_jours' => 7, 'relance_intervalle_jours' => 7]);
        $client = Client::create(['nom' => 'KONATE', 'email' => 'konate@example.com']);
        $enRetard = $this->facture($client, 10);
        $this->facture($client, 3); // pas encore assez en retard
        $this->facture(Client::create(['nom' => 'Sans email']), 30); // pas d'adresse

        $this->artisan('relances:envoyer', ['--simulation' => true])->assertSuccessful();
        Mail::assertNothingSent();

        $this->artisan('relances:envoyer')->assertSuccessful();
        Mail::assertSent(DocumentMail::class, fn (DocumentMail $m) => $m->relance && $m->document->is($enRetard));
        Mail::assertSent(DocumentMail::class, 1);
        $this->assertSame(1, $enRetard->fresh()->nb_relances);

        // Deja relancee aujourd'hui : rien de plus avant la fin de l'intervalle.
        $this->artisan('relances:envoyer')->assertSuccessful();
        Mail::assertSent(DocumentMail::class, 1);
    }

    public function test_l_ecran_des_relances_liste_les_retards(): void
    {
        $this->connecterAdmin();
        $this->facture(Client::create(['nom' => 'KONATE', 'telephone' => '70031250']), 12);

        Livewire::test(RelancesClients::class)
            ->assertSee('KONATE')
            ->assertSee('12 jour(s)')
            ->assertSee('https://wa.me/22670031250', false);
    }

    public function test_la_sauvegarde_automatique_garde_les_30_dernieres(): void
    {
        config(['filesystems.sauvegardes' => sys_get_temp_dir().'/tests-sauvegardes']);
        $admin = $this->connecterAdmin();
        $dossier = Sauvegarde::dossier($admin->entreprise_id);
        File::deleteDirectory($dossier);
        File::ensureDirectoryExists($dossier);
        for ($i = 0; $i < 31; $i++) {
            $chemin = $dossier."/ancienne_{$i}.zip";
            File::put($chemin, 'x');
            touch($chemin, now()->subDays(40 - $i)->timestamp);
        }

        $this->artisan('sauvegarde:automatique')->assertSuccessful();

        $liste = Sauvegarde::liste($admin->entreprise_id);
        $this->assertCount(Sauvegarde::A_CONSERVER, $liste);
        $this->assertStringContainsString('_auto_', $liste->first()['nom']);

        $this->get(route('sauvegarde.automatique', $liste->first()['nom']))->assertOk();
        $this->get(route('sauvegarde.automatique', '..%2F..%2F.env'))->assertNotFound();

        File::deleteDirectory($dossier);
    }

    public function test_scanner_un_code_barres_ajoute_puis_incremente_l_article(): void
    {
        $this->connecterAdmin();
        Article::create(['code' => 'C001', 'code_barre' => '6111234567890', 'designation' => 'BIC', 'prix_vente' => 200, 'stock_initial' => 50, 'stock_minimum' => 0, 'stock_disponible' => 50]);

        Livewire::test(Ventes::class)
            ->set('codeScan', '6111234567890')
            ->call('scanner')
            ->assertSet('lignes.0.quantite', 1)
            ->assertSet('lignes.0.prix_unitaire', '200.00')
            ->set('codeScan', '6111234567890')
            ->call('scanner')
            ->assertSet('lignes.0.quantite', 2)
            ->assertCount('lignes', 1)
            ->set('codeScan', 'INCONNU')
            ->call('scanner')
            ->assertHasErrors('codeScan');
    }

    public function test_l_application_est_installable(): void
    {
        $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('icons.0.src', '/icons/icon-192.png');

        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icons/icon-512.png'));
    }
}
