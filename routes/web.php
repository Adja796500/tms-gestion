<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\MonnaieARendreController;
use App\Http\Controllers\RecuController;
use App\Http\Controllers\VenteController;
use App\Livewire\Achats;
use App\Livewire\Archives;
use App\Livewire\Audit;
use App\Livewire\BonsCommande;
use App\Livewire\BordereauxLivraison;
use App\Livewire\Articles;
use App\Livewire\CategoriesActivite;
use App\Livewire\Clients;
use App\Livewire\Comptabilite\Acquisitions;
use App\Livewire\Comptabilite\EcrituresJournal;
use App\Livewire\Commandes;
use App\Livewire\CreancesClients;
use App\Livewire\Dashboard;
use App\Livewire\DettesFournisseurs;
use App\Livewire\FacturesDefinitives;
use App\Livewire\FacturesProforma;
use App\Livewire\Guide;
use App\Livewire\Fournisseurs;
use App\Livewire\HistoriqueVentes;
use App\Livewire\JournalActivite;
use App\Livewire\MonnaiesARendre;
use App\Livewire\MonRole;
use App\Livewire\MotDePasse;
use App\Livewire\Parametres;
use App\Livewire\Pilotage\RapportMensuel;
use App\Livewire\Pilotage\Statistiques;
use App\Livewire\Pilotage\Synthese;
use App\Livewire\Pilotage\Tresorerie;
use App\Livewire\ParametresEntreprise;
use App\Livewire\PaiementsClients;
use App\Livewire\Recus;
use App\Livewire\RegistreDocuments;
use App\Livewire\ReglementsFournisseurs;
use App\Livewire\RelancesClients;
use App\Livewire\Services;
use App\Livewire\SuperAdmin\Entreprises;
use App\Livewire\Utilisateurs;
use App\Livewire\Ventes;
use Illuminate\Support\Facades\Route;

Route::get('/manifest.webmanifest', function () {
    return response()->json([
        'name' => config('app.name'),
        'short_name' => config('app.name'),
        'description' => 'Ventes, stock, facturation et trésorerie',
        'lang' => 'fr',
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'any',
        'background_color' => '#1f3864',
        'theme_color' => '#1f3864',
        'icons' => [
            ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ['src' => '/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
        'shortcuts' => [
            ['name' => 'Nouvelle vente', 'url' => '/ventes', 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
            ['name' => 'Registre des documents', 'url' => '/registre-documents', 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json']);
})->name('manifest');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('login');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/', Dashboard::class)->middleware('module:ACCUEIL')->name('dashboard');
    Route::get('/mon-role', MonRole::class)->name('mon-role.index');

    Route::get('/articles', Articles::class)->middleware('module:ARTICLES')->name('articles.index');
    Route::get('/achats', Achats::class)->middleware('module:ACHATS_STOCK')->name('achats.index');
    Route::get('/ventes', Ventes::class)->middleware('module:'.Ventes::MODULES_CAISSE)->name('ventes.index');
    Route::get('/ventes/historique', HistoriqueVentes::class)->middleware('module:'.Ventes::MODULES_CAISSE)->name('ventes.historique');
    Route::get('/ventes/{vente}/imprimer', [VenteController::class, 'imprimer'])->middleware('module:'.Ventes::MODULES_CAISSE)->name('ventes.imprimer');
    Route::get('/monnaies-a-rendre', MonnaiesARendre::class)->middleware('module:'.Ventes::MODULES_CAISSE)->name('monnaies-a-rendre.index');
    Route::get('/monnaies-a-rendre/{monnaieARendre}/imprimer', [MonnaieARendreController::class, 'imprimer'])->middleware('module:'.Ventes::MODULES_CAISSE)->name('monnaies-a-rendre.imprimer');
    Route::get('/clients', Clients::class)->middleware('module:CLIENTS')->name('clients.index');
    Route::get('/fournisseurs', Fournisseurs::class)->middleware('module:FOURNISSEURS')->name('fournisseurs.index');
    Route::get('/paiements-clients', PaiementsClients::class)->middleware('module:PAIEMENTS_CLIENTS')->name('paiements-clients.index');
    Route::get('/reglements-fournisseurs', ReglementsFournisseurs::class)->middleware('module:REGLEMENTS_FOURNISSEURS')->name('reglements-fournisseurs.index');
    Route::get('/creances-clients', CreancesClients::class)->middleware('module:CREANCES_CLIENTS')->name('creances-clients.index');
    Route::get('/dettes-fournisseurs', DettesFournisseurs::class)->middleware('module:DETTES_FOURNISSEURS')->name('dettes-fournisseurs.index');
    Route::get('/factures/proforma', FacturesProforma::class)->middleware('module:FACTURE_PROFORMA')->name('factures.proforma');
    Route::get('/factures/definitives', FacturesDefinitives::class)->middleware('module:FACTURE_DEFINITIVE')->name('factures.definitives');
    Route::get('/factures/{facture}/imprimer', [FactureController::class, 'imprimer'])->name('factures.imprimer');
    Route::get('/recus', Recus::class)->middleware('module:RECU')->name('recus.index');
    Route::get('/recus/{recu}/imprimer', [RecuController::class, 'imprimer'])->middleware('module:RECU')->name('recus.imprimer');
    Route::get('/utilisateurs', Utilisateurs::class)->middleware('module:UTILISATEURS')->name('utilisateurs.index');
    Route::get('/parametres', Parametres::class)->middleware('module:PARAMETRES')->name('parametres.index');
    Route::get('/journal', JournalActivite::class)->middleware('module:JOURNAL')->name('journal.index');

    Route::get('/configuration-entreprise', ParametresEntreprise::class)->middleware('module:CONFIGURATION_ENTREPRISE')->name('configuration-entreprise.index');
    Route::get('/categories-activite', CategoriesActivite::class)->middleware('module:CATEGORIES_ACTIVITE')->name('categories-activite.index');
    Route::get('/comptabilite/journal/exporter', [ExportController::class, 'journal'])->middleware('module:COMPTABILITE_JOURNAL')->name('comptabilite.journal.exporter');
    Route::get('/comptabilite/journal', EcrituresJournal::class)->middleware('module:COMPTABILITE_JOURNAL')->name('comptabilite.journal.index');
    Route::get('/comptabilite/acquisitions', Acquisitions::class)->middleware('module:COMPTABILITE_ACQUISITIONS')->name('comptabilite.acquisitions.index');
    Route::get('/commandes', Commandes::class)->middleware('module:COMMANDES')->name('commandes.index');
    Route::get('/services', Services::class)->middleware('module:SERVICES')->name('services.index');

    // Feuilles de pilotage et de suivi du classeur (codes modules = noms des feuilles dans DROITS_ACCES).
    Route::get('/tresorerie', Tresorerie::class)->middleware('module:TRESORERIE')->name('tresorerie.index');
    Route::get('/rapport-mensuel', RapportMensuel::class)->middleware('module:RAPPORT MENSUEL')->name('rapport-mensuel.index');
    Route::get('/synthese', Synthese::class)->middleware('module:SYNTHESE')->name('synthese.index');
    Route::get('/statistiques', Statistiques::class)->middleware('module:STATISTIQUES')->name('statistiques.index');
    Route::get('/bordereaux', BordereauxLivraison::class)->middleware('module:BORDEREAU_LIVRAISON')->name('bordereaux.index');
    Route::get('/bons-commande', BonsCommande::class)->middleware('module:BON_COMMANDE')->name('bons-commande.index');
    Route::get('/documents/{document}/imprimer', [DocumentController::class, 'imprimer'])->name('documents.imprimer');
    Route::get('/documents/{document}/pdf', [DocumentController::class, 'pdf'])->name('documents.pdf');
    Route::get('/registre-documents', RegistreDocuments::class)->middleware('module:REGISTRE_DOCUMENTS')->name('registre-documents.index');
    Route::get('/archives', Archives::class)->middleware('module:ARCHIVES')->name('archives.index');
    Route::get('/archives/{mois}/exporter', [ExportController::class, 'mois'])->middleware('module:ARCHIVES')->name('archives.exporter');
    Route::get('/sauvegarde', [ExportController::class, 'sauvegarde'])->middleware('module:ARCHIVES')->name('sauvegarde.telecharger');
    Route::get('/sauvegardes/{fichier}', [ExportController::class, 'sauvegardeAutomatique'])->middleware('module:ARCHIVES')->name('sauvegarde.automatique');
    Route::get('/relances', RelancesClients::class)->middleware('module:CREANCES_CLIENTS')->name('relances.index');
    Route::get('/audit', Audit::class)->middleware('module:AUDIT')->name('audit.index');
    Route::get('/guide', Guide::class)->middleware('module:GUIDE')->name('guide.index');
    Route::get('/mot-de-passe', MotDePasse::class)->name('mot-de-passe.index');
});

Route::middleware(['auth', 'super-admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/entreprises', Entreprises::class)->name('entreprises');
});
