<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class NumeroGenerator
{
    /**
     * Genere un numero de document sequentiel du type PREFIX-ANNEE-000001.
     *
     * S'appuie sur la table document_sequences (une ligne par entreprise/table/annee)
     * verrouillee via lockForUpdate() pour serialiser les appels concurrents et eviter
     * toute collision de numero (l'ancienne implementation lisait le dernier numero
     * existant sans verrou, ce qui pouvait produire deux fois le meme numero en cas
     * de creations simultanees).
     *
     * $entrepriseId est requis : cette methode utilise le query builder brut (pas Eloquent),
     * donc le cloisonnement par entreprise n'est pas applique automatiquement ici.
     *
     * Laravel gere les transactions imbriquees via des savepoints : appeler cette methode
     * depuis un DB::transaction() englobant (cas de Achats.php, Ventes.php, etc.) reste sur.
     */
    public static function suivant(string $table, string $prefixe, int $entrepriseId, ?string $colonne = 'numero'): string
    {
        $annee = now()->year;

        return DB::transaction(function () use ($table, $prefixe, $entrepriseId, $annee) {
            DB::table('document_sequences')->insertOrIgnore([
                'entreprise_id' => $entrepriseId,
                'table_name' => $table,
                'annee' => $annee,
                'dernier_numero' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('document_sequences')
                ->where('entreprise_id', $entrepriseId)
                ->where('table_name', $table)
                ->where('annee', $annee)
                ->lockForUpdate()
                ->first();

            $suivant = $sequence->dernier_numero + 1;

            DB::table('document_sequences')->where('id', $sequence->id)
                ->update(['dernier_numero' => $suivant, 'updated_at' => now()]);

            return sprintf('%s-%d-%06d', $prefixe, $annee, $suivant);
        });
    }

    /**
     * Variante utilisee pour les factures proforma/definitives : le modele papier de
     * l'entreprise utilise le format 018/TMS/2026 (sequence sur 3 chiffres / sigle de
     * l'entreprise / annee) plutot que PREFIX-ANNEE-000001. S'appuie sur la meme table
     * document_sequences (meme verrou, meme compteur par entreprise/table/annee) donc
     * partage la sequence avec suivant() si on l'appelait sur la meme cle $table.
     */
    public static function suivantAvecSigle(string $table, string $sigle, int $entrepriseId): string
    {
        $annee = now()->year;

        return DB::transaction(function () use ($table, $sigle, $entrepriseId, $annee) {
            DB::table('document_sequences')->insertOrIgnore([
                'entreprise_id' => $entrepriseId,
                'table_name' => $table,
                'annee' => $annee,
                'dernier_numero' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('document_sequences')
                ->where('entreprise_id', $entrepriseId)
                ->where('table_name', $table)
                ->where('annee', $annee)
                ->lockForUpdate()
                ->first();

            $suivant = $sequence->dernier_numero + 1;

            DB::table('document_sequences')->where('id', $sequence->id)
                ->update(['dernier_numero' => $suivant, 'updated_at' => now()]);

            return sprintf('%03d/%s/%d', $suivant, $sigle, $annee);
        });
    }
}
