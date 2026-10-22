<?php

namespace App\Support;

use App\Models\Achat;
use App\Models\Acquisition;
use App\Models\Article;
use App\Models\CategorieActivite;
use App\Models\Client;
use App\Models\Commande;
use App\Models\EcritureJournal;
use App\Models\Entreprise;
use App\Models\Facture;
use App\Models\FactureLigne;
use App\Models\Fournisseur;
use App\Models\PaiementClient;
use App\Models\Recu;
use App\Models\ReglementFournisseur;
use App\Models\Vente;
use App\Models\VenteLigne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Sauvegarde complete des donnees d'une entreprise : un ZIP de fichiers CSV
 * (separateur ";" + BOM UTF-8, lisibles dans Excel). Utilisee par le bouton
 * "Telecharger la sauvegarde" et par la sauvegarde automatique de la nuit, qui
 * conserve les 30 dernieres dans storage/app/sauvegardes/{entreprise}.
 */
class Sauvegarde
{
    public const A_CONSERVER = 30;

    public static function dossier(int $entrepriseId): string
    {
        // Configurable (les tests utilisent un dossier temporaire, jamais les vraies sauvegardes).
        return config('filesystems.sauvegardes', storage_path('app/sauvegardes')).'/'.$entrepriseId;
    }

    /**
     * Cree le ZIP a l'emplacement donne et retourne son chemin.
     */
    public static function creerZip(int $entrepriseId, string $chemin): string
    {
        $parEntreprise = fn (string $modele) => $modele::withoutGlobalScopes()->where('entreprise_id', $entrepriseId);
        $venteIds = Vente::withoutGlobalScopes()->where('entreprise_id', $entrepriseId)->pluck('id');
        $factureIds = Facture::withoutGlobalScopes()->where('entreprise_id', $entrepriseId)->pluck('id');

        $jeux = [
            'clients' => $parEntreprise(Client::class),
            'fournisseurs' => $parEntreprise(Fournisseur::class),
            'categories_activite' => $parEntreprise(CategorieActivite::class),
            'articles' => $parEntreprise(Article::class),
            'achats' => $parEntreprise(Achat::class),
            'ventes' => $parEntreprise(Vente::class),
            'vente_lignes' => VenteLigne::query()->whereIn('vente_id', $venteIds),
            'paiements_clients' => $parEntreprise(PaiementClient::class),
            'reglements_fournisseurs' => $parEntreprise(ReglementFournisseur::class),
            'journal_transactions' => $parEntreprise(EcritureJournal::class),
            'acquisitions' => $parEntreprise(Acquisition::class),
            'documents' => $parEntreprise(Facture::class),
            'document_lignes' => FactureLigne::query()->whereIn('facture_id', $factureIds),
            'recus' => $parEntreprise(Recu::class),
            'commandes' => $parEntreprise(Commande::class),
        ];

        File::ensureDirectoryExists(dirname($chemin));
        $zip = new ZipArchive();
        $zip->open($chemin, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($jeux as $fichier => $requete) {
            $lignes = $requete->orderBy($requete->getModel()->getKeyName())->get()->map->getAttributes()->all();
            $zip->addFromString($fichier.'.csv', self::csv($lignes));
        }

        $zip->close();

        return $chemin;
    }

    public static function nomFichier(int $entrepriseId, string $suffixe = 'sauvegarde'): string
    {
        $nom = Entreprise::find($entrepriseId)?->nom ?? 'entreprise';

        return (Str::slug($nom, '_') ?: 'entreprise').'_'.$suffixe.'_'.now()->format('Y-m-d_H-i').'.zip';
    }

    /**
     * Sauvegarde automatique : cree le fichier du jour et supprime les plus anciens.
     */
    public static function automatique(int $entrepriseId): string
    {
        $chemin = self::creerZip($entrepriseId, self::dossier($entrepriseId).'/'.self::nomFichier($entrepriseId, 'auto'));

        self::liste($entrepriseId)->slice(self::A_CONSERVER)->each(fn ($f) => File::delete($f['chemin']));

        return $chemin;
    }

    /**
     * Sauvegardes automatiques disponibles, de la plus recente a la plus ancienne.
     *
     * @return Collection<int, array{nom: string, chemin: string, taille: int, date: \Carbon\Carbon}>
     */
    public static function liste(int $entrepriseId): Collection
    {
        $dossier = self::dossier($entrepriseId);

        if (! is_dir($dossier)) {
            return collect();
        }

        return collect(File::files($dossier))
            ->filter(fn ($f) => $f->getExtension() === 'zip')
            ->map(fn ($f) => [
                'nom' => $f->getFilename(),
                'chemin' => $f->getPathname(),
                'taille' => $f->getSize(),
                'date' => \Carbon\Carbon::createFromTimestamp($f->getMTime()),
            ])
            ->sortByDesc('date')
            ->values();
    }

    /**
     * @param  array<int, array<string, mixed>>  $lignes
     */
    public static function csv(array $lignes): string
    {
        $flux = fopen('php://temp', 'r+');
        fwrite($flux, "\xEF\xBB\xBF");

        if ($lignes !== []) {
            fputcsv($flux, array_keys($lignes[0]), ';');
            foreach ($lignes as $ligne) {
                fputcsv($flux, array_values($ligne), ';');
            }
        }

        rewind($flux);
        $contenu = stream_get_contents($flux);
        fclose($flux);

        return $contenu;
    }
}
