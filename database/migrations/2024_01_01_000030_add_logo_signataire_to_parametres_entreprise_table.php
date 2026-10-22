<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametres_entreprise', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('nom_entreprise');
            $table->string('signataire')->nullable()->after('ifu');
        });
    }

    public function down(): void
    {
        Schema::table('parametres_entreprise', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'signataire']);
        });
    }
};
