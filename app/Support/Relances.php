<?php

namespace App\Support;

use App\Models\Facture;
use App\Models\ParametreEntreprise;
use Illuminate\Support\Collection;

/**
 * Factures definitives non soldees et regles de relance. Les requetes filtrent
 * explicitement par entreprise : elles tournent aussi la nuit, sans utilisateur
 * connecte (donc sans le cloisonnement automatique).
 */
class Relances
{
    /**
     * Factures avec un reste a payer, de la plus ancienne a la plus recente, avec
     * reste et jours de retard calcules.
     *
     * @return Collection<int, Facture>
     */
    public static function impayees(int $entrepriseId): Collection
    {
        return Facture::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->where('type', Facture::TYPE_DEFINITIVE)
            ->where('statut', '!=', 'Annulée')
            ->with(['client' => fn ($q) => $q->withoutGlobalScopes()])
            ->withSum(['paiements' => fn ($q) => $q->withoutGlobalScopes()], 'montant')
            ->orderBy('date')
            ->get()
            ->each(function (Facture $f) {
                $f->reste = round((float) $f->montant_total - (float) $f->paiements_sum_montant, 2);
                $f->jours_retard = (int) $f->date->startOfDay()->diffInDays(now()->startOfDay());
            })
            ->filter(fn (Facture $f) => $f->reste > 0)
            ->values();
    }

    /**
     * Factures a relancer automatiquement aujourd'hui selon les reglages : assez en
     * retard, avec un email, et pas deja relancees pendant l'intervalle choisi.
     *
     * @return Collection<int, Facture>
     */
    public static function aRelancer(ParametreEntreprise $parametres): Collection
    {
        $delai = max(1, (int) $parametres->relance_delai_jours);
        $intervalle = max(1, (int) $parametres->relance_intervalle_jours);

        return self::impayees($parametres->entreprise_id)
            ->filter(fn (Facture $f) => $f->jours_retard >= $delai)
            ->filter(fn (Facture $f) => ! $f->derniere_relance_le || $f->derniere_relance_le->lte(now()->subDays($intervalle)))
            ->filter(fn (Facture $f) => EnvoiDocument::adresse($f) !== null)
            ->values();
    }
}
