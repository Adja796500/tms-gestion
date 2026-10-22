<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametres_entreprise', function (Blueprint $table) {
            $table->string('sigle')->nullable()->after('nom_entreprise');
        });
    }

    public function down(): void
    {
        Schema::table('parametres_entreprise', function (Blueprint $table) {
            $table->dropColumn('sigle');
        });
    }
};
