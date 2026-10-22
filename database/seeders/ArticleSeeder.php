<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Entreprise;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $entreprise = Entreprise::firstOrCreate(['nom' => 'ENTREPRISE DAME'], ['actif' => true]);

        $donnees = DameDataFile::charger()['articles'] ?? [];

        foreach ($donnees as $ligne) {
            $code = $ligne['Code'] ?? null;

            if (! $code) {
                continue;
            }

            $article = Article::updateOrCreate(
                ['code' => $code, 'entreprise_id' => $entreprise->id],
                [
                    'designation' => $ligne['Désignation'] ?? $code,
                    'categorie' => $ligne['Catégorie'] ?? null,
                    'unite' => $ligne['Unité'] ?? null,
                    'prix_achat' => $ligne["Prix d'achat (FCFA)"] ?? null,
                    'prix_vente' => $ligne['Prix de vente (FCFA)'] ?? 0,
                    'stock_initial' => (int) ($ligne['Stock initial'] ?? 0),
                    'stock_minimum' => (int) ($ligne['Stock minimum'] ?? 0),
                ]
            );

            $article->stock_disponible = $article->stock_initial;
            $article->save();
        }
    }
}
