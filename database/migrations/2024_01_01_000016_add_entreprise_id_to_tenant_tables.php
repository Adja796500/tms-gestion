<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables metier rattachees obligatoirement a une entreprise.
     */
    private const TABLES_OBLIGATOIRES = [
        'clients',
        'fournisseurs',
        'articles',
        'achats',
        'ventes',
        'paiements_clients',
        'reglements_fournisseurs',
        'factures',
        'recus',
    ];

    public function up(): void
    {
        $entrepriseId = DB::table('entreprises')->where('nom', 'ENTREPRISE DAME')->value('id');

        foreach (self::TABLES_OBLIGATOIRES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('entreprise_id')->nullable()->after('id')->constrained('entreprises')->cascadeOnDelete();
            });

            DB::table($table)->whereNull('entreprise_id')->update(['entreprise_id' => $entrepriseId]);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('entreprise_id')->nullable(false)->change();
            });
        }

        // access_rules : la meme regle peut maintenant differer d'une entreprise a l'autre.
        Schema::table('access_rules', function (Blueprint $table) {
            $table->foreignId('entreprise_id')->nullable()->after('id')->constrained('entreprises')->cascadeOnDelete();
        });

        DB::table('access_rules')->whereNull('entreprise_id')->update(['entreprise_id' => $entrepriseId]);

        Schema::table('access_rules', function (Blueprint $table) {
            $table->foreignId('entreprise_id')->nullable(false)->change();
            $table->dropUnique(['module', 'role']);
            $table->unique(['entreprise_id', 'module', 'role']);
        });

        // activity_logs : nullable, certaines actions (ex: creation d'une entreprise
        // par le super-administrateur) n'appartiennent a aucune entreprise precise.
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('entreprise_id')->nullable()->after('id')->constrained('entreprises')->nullOnDelete();
        });

        DB::table('activity_logs')->whereNull('entreprise_id')->update(['entreprise_id' => $entrepriseId]);
    }

    public function down(): void
    {
        foreach ([...self::TABLES_OBLIGATOIRES, 'activity_logs'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('entreprise_id');
            });
        }

        Schema::table('access_rules', function (Blueprint $table) {
            $table->dropUnique(['entreprise_id', 'module', 'role']);
            $table->dropConstrainedForeignId('entreprise_id');
            $table->unique(['module', 'role']);
        });
    }
};
