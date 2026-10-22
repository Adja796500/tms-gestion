<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->string('mode_paiement')->default('Especes')->after('remise_pourcentage');
            $table->decimal('montant_remis', 14, 2)->nullable()->after('mode_paiement');
        });
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropColumn(['mode_paiement', 'montant_remis']);
        });
    }
};
