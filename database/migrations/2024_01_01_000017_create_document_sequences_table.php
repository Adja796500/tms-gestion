<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sources reelles utilisees par NumeroGenerator::suivant() avant ce fix, avec le
     * prefixe de numero associe. Pour les factures, proforma et definitive utilisent
     * des cles de sequence distinctes ('factures_proforma' / 'factures_definitive')
     * bien qu'elles partagent la meme table 'factures', pour ne pas faire fusionner
     * deux compteurs qui etaient independants jusqu'ici (chacun filtre par prefixe).
     *
     * Format: [table source, cle de sequence document_sequences, prefixe, [colonne, valeur] filtre optionnel]
     */
    private const SOURCES = [
        ['achats', 'achats', 'ACH', null],
        ['ventes', 'ventes', 'VEN', null],
        ['paiements_clients', 'paiements_clients', 'PAI', null],
        ['reglements_fournisseurs', 'reglements_fournisseurs', 'REG', null],
        ['recus', 'recus', 'REC', null],
        ['factures', 'factures_proforma', 'PRO', ['type', 'proforma']],
        ['factures', 'factures_definitive', 'FAC', ['type', 'definitive']],
    ];

    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();
            $table->string('table_name');
            $table->unsignedSmallInteger('annee');
            $table->unsignedBigInteger('dernier_numero')->default(0);
            $table->timestamps();

            $table->unique(['entreprise_id', 'table_name', 'annee']);
        });

        foreach (self::SOURCES as [$tableSource, $sequenceKey, $prefixe, $filtre]) {
            $this->backfillSequence($tableSource, $sequenceKey, $prefixe, $filtre);
        }
    }

    /**
     * Reprend, pour chaque entreprise et chaque annee deja presente dans les numeros
     * existants, le plus grand numero de sequence deja attribue (parse de la partie
     * apres le dernier '-' de 'PREFIXE-ANNEE-NNNNNN'), pour que le nouveau systeme
     * base sur document_sequences continue a partir de la, sans percuter les numeros
     * deja distribues.
     */
    private function backfillSequence(string $tableSource, string $sequenceKey, string $prefixe, ?array $filtre): void
    {
        if (! Schema::hasTable($tableSource)) {
            return;
        }

        $query = DB::table($tableSource)->select('entreprise_id', 'numero');

        if ($filtre !== null) {
            [$colonne, $valeur] = $filtre;
            $query->where($colonne, $valeur);
        }

        $maxParEntrepriseEtAnnee = [];

        foreach ($query->get() as $ligne) {
            if (! preg_match('/^'.preg_quote($prefixe, '/').'-(\d{4})-(\d+)$/', (string) $ligne->numero, $matches)) {
                continue;
            }

            $annee = (int) $matches[1];
            $sequence = (int) $matches[2];

            $courant = $maxParEntrepriseEtAnnee[$ligne->entreprise_id][$annee] ?? 0;
            $maxParEntrepriseEtAnnee[$ligne->entreprise_id][$annee] = max($courant, $sequence);
        }

        foreach ($maxParEntrepriseEtAnnee as $entrepriseId => $parAnnee) {
            foreach ($parAnnee as $annee => $dernierNumero) {
                DB::table('document_sequences')->updateOrInsert(
                    ['entreprise_id' => $entrepriseId, 'table_name' => $sequenceKey, 'annee' => $annee],
                    ['dernier_numero' => $dernierNumero, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
