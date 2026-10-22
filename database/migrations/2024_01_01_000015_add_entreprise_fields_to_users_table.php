<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('entreprise_id')->nullable()->after('id')->constrained('entreprises')->nullOnDelete();
            $table->boolean('est_super_administrateur')->default(false)->after('actif');
        });

        // Tous les utilisateurs existants appartiennent a la premiere entreprise
        // (ENTREPRISE DAME, creee par la migration precedente).
        $entrepriseId = DB::table('entreprises')->where('nom', 'ENTREPRISE DAME')->value('id');

        DB::table('users')->whereNull('entreprise_id')->update(['entreprise_id' => $entrepriseId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entreprise_id');
            $table->dropColumn('est_super_administrateur');
        });
    }
};
