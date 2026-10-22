<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - Envoi des documents par email et relances automatiques des impayes :
     *   reglages par entreprise + suivi par facture (date d'envoi, relances).
     * - Caisse rapide : code-barres des articles (douchette ou camera).
     */
    public function up(): void
    {
        Schema::table('parametres_entreprise', function (Blueprint $table) {
            $table->boolean('envoi_auto_factures')->default(false);
            $table->boolean('relances_actives')->default(false);
            $table->unsignedSmallInteger('relance_delai_jours')->default(7);
            $table->unsignedSmallInteger('relance_intervalle_jours')->default(7);
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->timestamp('envoyee_le')->nullable();
            $table->timestamp('derniere_relance_le')->nullable();
            $table->unsignedSmallInteger('nb_relances')->default(0);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->string('code_barre', 64)->nullable()->after('code');
            $table->index(['entreprise_id', 'code_barre']);
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['entreprise_id', 'code_barre']);
            $table->dropColumn('code_barre');
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn(['envoyee_le', 'derniere_relance_le', 'nb_relances']);
        });

        Schema::table('parametres_entreprise', function (Blueprint $table) {
            $table->dropColumn(['envoi_auto_factures', 'relances_actives', 'relance_delai_jours', 'relance_intervalle_jours']);
        });
    }
};
