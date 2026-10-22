<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reprend les dernieres feuilles du classeur :
     * - BORDEREAU_LIVRAISON et BON_COMMANDE : nouveaux types de la table factures.
     *   Le bon de commande est adresse a un fournisseur (client_id devient
     *   facultatif), le bordereau peut citer la facture qu'il livre et chaque
     *   ligne peut porter une observation ;
     * - REGISTRE_DOCUMENTS : "Montant paye (auto)" = paiements clients rattaches au
     *   N° du document, d'ou facture_id sur paiements_clients ;
     * - ARCHIVES : une ligne par mois cloture, avec les chiffres figes a la cloture.
     */
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->change();
            $table->foreignId('fournisseur_id')->nullable()->after('client_id')->constrained('fournisseurs')->nullOnDelete();
            $table->foreignId('facture_liee_id')->nullable()->after('fournisseur_id')->constrained('factures')->nullOnDelete();
            $table->decimal('remise_pourcentage', 5, 2)->default(0)->after('montant_total');
        });

        Schema::table('facture_lignes', function (Blueprint $table) {
            $table->decimal('prix_unitaire', 14, 2)->default(0)->change();
            $table->decimal('total', 14, 2)->default(0)->change();
            $table->string('observations')->nullable()->after('total');
        });

        Schema::table('paiements_clients', function (Blueprint $table) {
            $table->foreignId('facture_id')->nullable()->after('vente_id')->constrained('factures')->nullOnDelete();
        });

        Schema::create('archives_mensuelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();
            $table->date('mois'); // premier jour du mois archive
            $table->unsignedInteger('nb_factures')->default(0);
            $table->decimal('chiffre_affaires', 14, 2)->default(0);
            $table->decimal('depenses', 14, 2)->default(0);
            $table->decimal('benefice', 14, 2)->default(0);
            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['entreprise_id', 'mois']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archives_mensuelles');

        Schema::table('paiements_clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facture_id');
        });

        Schema::table('facture_lignes', function (Blueprint $table) {
            $table->dropColumn('observations');
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn('remise_pourcentage');
            $table->dropConstrainedForeignId('facture_liee_id');
            $table->dropConstrainedForeignId('fournisseur_id');
        });
    }
};
