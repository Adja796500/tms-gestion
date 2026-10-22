<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Cloisonne automatiquement un modele par entreprise : chaque requete est filtree
 * a l'entreprise de l'utilisateur connecte, et chaque creation lui est rattachee
 * automatiquement. Le super-administrateur et les contextes sans utilisateur
 * connecte (seeders, console) ne sont pas filtres.
 */
trait AppartientAUneEntreprise
{
    protected static function bootAppartientAUneEntreprise(): void
    {
        static::addGlobalScope('entreprise', function (Builder $query) {
            $user = auth()->user();

            if (! $user || $user->est_super_administrateur) {
                return;
            }

            $query->where($query->getModel()->getTable().'.entreprise_id', $user->entreprise_id);
        });

        static::creating(function (Model $model) {
            $user = auth()->user();

            if ($user && ! $user->est_super_administrateur && empty($model->entreprise_id)) {
                $model->entreprise_id = $user->entreprise_id;
            }
        });
    }
}
