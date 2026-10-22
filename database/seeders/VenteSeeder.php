<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Client;
use App\Models\Entreprise;
use App\Models\Vente;
use App\Models\VenteLigne;
use App\Support\NumeroGenerator;
use Illuminate\Database\Seeder;

class VenteSeeder extends Seeder
{
    public function run(): void
    {
        $entreprise = Entreprise::firstOrCreate(['nom' => 'ENTREPRISE DAME'], ['actif' => true]);

        $donnees = DameDataFile::charger()['ventes'] ?? [];

        foreach ($donnees as $ligne) {
            $date = $ligne['Date'] ?? null;
            $codeArticle = $ligne['Code Article'] ?? null;
            $nomClient = $ligne['Client'] ?? null;

            if (! $date || ! $codeArticle || ! $nomClient) {
                continue;
            }

            $article = Article::where('code', $codeArticle)->where('entreprise_id', $entreprise->id)->first();

            if (! $article) {
                continue;
            }

            // Certains clients cites dans les ventes du classeur d'origine n'existaient pas
            // dans la feuille CLIENTS (incoherence du fichier source) : on les cree a la volee.
            $client = Client::firstOrCreate(['nom' => $nomClient, 'entreprise_id' => $entreprise->id]);

            $quantite = (int) ($ligne['Quantité'] ?? 0);

            if ($quantite <= 0) {
                continue;
            }

            $prixUnitaire = (float) ($ligne['Prix de vente unitaire (FCFA)'] ?? $article->prix_vente ?? 0);
            $total = (float) ($ligne['Total (FCFA)'] ?? ($quantite * $prixUnitaire));

            $vente = Vente::create([
                'entreprise_id' => $entreprise->id,
                'numero' => NumeroGenerator::suivant('ventes', 'VEN', $entreprise->id),
                'date' => $date,
                'client_id' => $client->id,
                'total' => $total,
            ]);

            VenteLigne::create([
                'vente_id' => $vente->id,
                'article_id' => $article->id,
                'quantite' => $quantite,
                'prix_unitaire' => $prixUnitaire,
                'total' => $total,
            ]);
        }

        Article::where('entreprise_id', $entreprise->id)->get()->each->recalculerStock();
    }
}
