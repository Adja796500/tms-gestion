<?php

namespace App\Http\Controllers;

use App\Models\Achat;
use App\Models\Acquisition;
use App\Models\ActivityLog;
use App\Models\EcritureJournal;
use App\Models\Facture;
use App\Models\PaiementClient;
use App\Models\ReglementFournisseur;
use App\Models\Vente;
use App\Support\Sauvegarde;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Equivalent web des macros SauvegarderClasseur / ArchiverLeMois : au lieu d'une
 * copie du classeur, un ZIP de fichiers CSV (separateur ";" et encodage UTF-8 avec
 * BOM, pour s'ouvrir directement dans Excel). Toutes les requetes passent par
 * Eloquent : seules les donnees de l'entreprise connectee sont exportees.
 */
class ExportController extends Controller
{
    /**
     * Sauvegarde complete des donnees de l'entreprise (meme contenu que la
     * sauvegarde automatique de la nuit, voir App\Support\Sauvegarde).
     */
    public function sauvegarde(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->peutModifier('ARCHIVES'), 403);

        $entrepriseId = $request->user()->entreprise_id;
        $nom = Sauvegarde::nomFichier($entrepriseId);
        $chemin = Sauvegarde::creerZip($entrepriseId, tempnam(sys_get_temp_dir(), 'sauvegarde'));
        ActivityLog::enregistrer('sauvegarde', 'ARCHIVES', null, null, null, $nom);

        return response()->download($chemin, $nom, ['Content-Type' => 'application/zip'])->deleteFileAfterSend();
    }

    /**
     * Telechargement d'une sauvegarde automatique de l'entreprise connectee. Le nom
     * est verifie contre la liste reelle du dossier : impossible de remonter
     * l'arborescence ou d'atteindre les sauvegardes d'une autre entreprise.
     */
    public function sauvegardeAutomatique(Request $request, string $fichier): BinaryFileResponse
    {
        abort_unless($request->user()->peutModifier('ARCHIVES'), 403);

        $sauvegarde = Sauvegarde::liste($request->user()->entreprise_id)->firstWhere('nom', $fichier);
        abort_unless($sauvegarde, 404);

        ActivityLog::enregistrer('telechargement_sauvegarde', 'ARCHIVES', null, null, null, $fichier);

        return response()->download($sauvegarde['chemin'], $fichier, ['Content-Type' => 'application/zip']);
    }

    /**
     * Export du Journal des transactions avec les filtres de l'ecran, en un seul
     * fichier CSV lisible par Excel (colonnes du classeur, libelles en clair).
     */
    public function journal(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless($request->user()->peutAccederA('COMPTABILITE_JOURNAL'), 403);

        $lignes = EcritureJournal::query()
            ->filtrer($request->only(['du', 'au', 'categorie', 'type', 'compte', 'recherche']))
            ->with('categorieActivite')
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->map(fn (EcritureJournal $e) => [
                'Date' => $e->date->format('d/m/Y'),
                'Mois' => $e->mois(),
                'Catégorie' => $e->categorieActivite->nom ?? '',
                'Type' => $e->libelleType(),
                'Description' => $e->description,
                'Montant (FCFA)' => (float) $e->montant,
                'Mode de paiement' => $e->libelleMoyenPaiement(),
                'Compte' => $e->compte,
            ])
            ->all();

        ActivityLog::enregistrer('export', 'COMPTABILITE_JOURNAL', null, null, null, count($lignes).' ligne(s)');

        return response(Sauvegarde::csv($lignes), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->prefixe().'_journal_'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    /**
     * Archive d'un mois (format AAAA-MM) : mouvements dates de ce mois.
     */
    public function mois(Request $request, string $mois): BinaryFileResponse
    {
        abort_unless($request->user()->peutAccederA('ARCHIVES'), 403);
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $mois) === 1, 404);

        $debut = CarbonImmutable::createFromFormat('Y-m-d', $mois.'-01')->startOfDay();
        $fin = $debut->endOfMonth();
        $periode = fn (Builder $q) => $q->whereBetween('date', [$debut->toDateString(), $fin->toDateString()]);

        $jeux = [
            'journal_transactions' => $periode(EcritureJournal::query()),
            'ventes' => $periode(Vente::query()),
            'achats' => $periode(Achat::query()),
            'paiements_clients' => $periode(PaiementClient::query()),
            'reglements_fournisseurs' => $periode(ReglementFournisseur::query()),
            'acquisitions' => $periode(Acquisition::query()),
            'documents' => $periode(Facture::query()),
        ];

        $libelle = Str::slug(\App\Support\Finances::libelleMois($debut), '_');
        $nom = $this->prefixe().'_'.$libelle.'.zip';
        ActivityLog::enregistrer('export', 'ARCHIVES', null, null, null, $nom);

        return $this->zip($nom, $jeux);
    }

    /**
     * @param  array<string, Builder>  $jeux
     */
    private function zip(string $nom, array $jeux): BinaryFileResponse
    {
        $chemin = tempnam(sys_get_temp_dir(), 'export');
        $zip = new ZipArchive();
        $zip->open($chemin, ZipArchive::OVERWRITE);

        foreach ($jeux as $fichier => $requete) {
            $zip->addFromString($fichier.'.csv', Sauvegarde::csv($requete->orderBy($requete->getModel()->getKeyName())->get()->map->getAttributes()->all()));
        }

        $zip->close();

        return response()->download($chemin, $nom, ['Content-Type' => 'application/zip'])->deleteFileAfterSend();
    }

    private function prefixe(): string
    {
        $nom = auth()->user()->entreprise?->nom ?? config('app.name');

        return Str::slug($nom, '_') ?: 'entreprise';
    }
}
