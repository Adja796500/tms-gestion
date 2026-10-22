<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Proformas et factures partageaient le format NNN/SIGLE/ANNEE avec deux compteurs
     * distincts : la facture 008/TMS/2026 serait entree en collision avec la proforma
     * deja numerotee 008/TMS/2026. Les proformas portent desormais le prefixe "P-".
     */
    public function up(): void
    {
        DB::table('factures')
            ->where('type', 'proforma')
            ->where('numero', 'not like', 'P-%')
            ->where('numero', 'like', '%/%/%')
            ->update(['numero' => DB::raw("'P-' || numero")]);
    }

    public function down(): void
    {
        DB::table('factures')
            ->where('type', 'proforma')
            ->where('numero', 'like', 'P-%/%/%')
            ->update(['numero' => DB::raw('substr(numero, 3)')]);
    }
};
