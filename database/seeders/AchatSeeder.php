<?php

namespace Database\Seeders;

use App\Models\Achat;
use App\Models\Article;
use App\Models\Entreprise;
use App\Models\Fournisseur;
use App\Support\NumeroGenerator;
use Illuminate\Database\Seeder;

class AchatSeeder extends Seeder
{
    public function run(): void
    {
        $entreprise = Entreprise::firstOrCreate(['nom' => 'ENTREPRISE DAME'], ['actif' => true]);

        $donnees = DameDataFile::charger()['achats'] ?? [];

        foreach ($donnees as $ligne) {
            $date = $ligne['Date'] ?? null;
            $codeArticle = $ligne['Code Article'] ?? null;

            if (! $date || ! $codeArticle) {
                continue;
            }

            $article = Article::where('code', $codeArticle)->where('entreprise_id', $entreprise->id)->first();

            if (! $article) {
                continue;
            }

            $fournisseur = null;
            if (! empty($ligne['Fournisseur'])) {
                $fournisseur = Fournisseur::firstOrCreate(['nom' => $ligne['Fournisseur'], 'entreprise_id' => $entreprise->id]);
            }

            $quantite = (int) ($ligne['Quantité'] ?? 0);
            $prixUnitaire = (float) ($ligne["Prix d'achat unitaire (FCFA)"] ?? $article->prix_achat ?? 0);
            $total = (float) ($ligne['Total (FCFA)'] ?? ($quantite * $prixUnitaire));
            $modeReglement = ($ligne['Mode de règlement'] ?? 'Comptant') === 'Credit' ? 'Credit' : 'Comptant';
            $montantRegle = (float) ($ligne['Montant réglé (auto)'] ?? ($modeReglement === 'Comptant' ? $total : 0));

            $achat = Achat::create([
                'entreprise_id' => $entreprise->id,
                'numero' => NumeroGenerator::suivant('achats', 'ACH', $entreprise->id),
                'date' => $date,
                'article_id' => $article->id,
                'fournisseur_id' => $fournisseur?->id,
                'quantite' => $quantite,
                'prix_unitaire' => $prixUnitaire,
                'total' => $total,
                'mode_reglement' => $modeReglement,
                'montant_regle' => $montantRegle,
            ]);

            $achat->rafraichirStatut();
        }

        Article::where('entreprise_id', $entreprise->id)->get()->each->recalculerStock();
    }
}
