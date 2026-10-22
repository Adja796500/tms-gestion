<?php

namespace App\Providers;

use App\Models\AccessRule;
use App\Models\Achat;
use App\Models\Article;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\PaiementClient;
use App\Models\Recu;
use App\Models\ReglementFournisseur;
use App\Models\User;
use App\Models\Vente;
use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Vite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Modeles dont les creations/modifications/suppressions sont tracees
     * dans le journal d'activite (voir App\Observers\AuditObserver).
     */
    private const MODELES_AUDITES = [
        Vente::class,
        Achat::class,
        Client::class,
        Fournisseur::class,
        Article::class,
        PaiementClient::class,
        ReglementFournisseur::class,
        Facture::class,
        Recu::class,
        User::class,
        AccessRule::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        foreach (self::MODELES_AUDITES as $modele) {
            $modele::observe(AuditObserver::class);
        }

        // "existe_ici:clients" : comme "exists", mais uniquement dans l'entreprise de
        // l'utilisateur connecte. Empeche de rattacher a une saisie le client, l'article
        // ou la facture d'une autre entreprise en modifiant la requete envoyee au serveur.
        Validator::extend('existe_ici', function (string $attribut, $valeur, array $parametres): bool {
            $entrepriseId = auth()->user()?->entreprise_id;

            return $entrepriseId !== null && DB::table($parametres[0])
                ->where('id', $valeur)
                ->where('entreprise_id', $entrepriseId)
                ->exists();
        });
    }
}
