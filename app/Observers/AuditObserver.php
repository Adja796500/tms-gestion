<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    /**
     * Champs jamais journalisés (secrets).
     */
    private const CHAMPS_PROTEGES = ['password', 'remember_token'];

    public function created(Model $model): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $this->log('creation', $model, null, $this->nettoyer($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $apres = $model->getChanges();
        unset($apres['updated_at']);

        // La mise a jour de "derniere_connexion" est deja tracee par l'action
        // dediee "connexion" (voir AuthenticatedSessionController) : evite un doublon.
        if ($model instanceof User) {
            unset($apres['derniere_connexion']);
        }

        if (empty($apres)) {
            return;
        }

        $avant = collect($apres)->keys()
            ->mapWithKeys(fn ($champ) => [$champ => $model->getOriginal($champ)])
            ->toArray();

        $this->log('modification', $model, $this->nettoyer($avant), $this->nettoyer($apres));
    }

    public function deleted(Model $model): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $this->log('suppression', $model, $this->nettoyer($model->getAttributes()), null);
    }

    private function log(string $action, Model $model, ?array $avant, ?array $apres): void
    {
        ActivityLog::enregistrer(
            action: $action,
            module: class_basename($model),
            sujetId: $model->getKey(),
            avant: $avant,
            apres: $apres,
            libelle: $this->libelle($model),
        );
    }

    private function nettoyer(array $donnees): array
    {
        foreach (self::CHAMPS_PROTEGES as $champ) {
            if (array_key_exists($champ, $donnees)) {
                $donnees[$champ] = '[protege]';
            }
        }

        return $donnees;
    }

    private function libelle(Model $model): string
    {
        if (isset($model->module, $model->role)) {
            return "{$model->module} / {$model->role}";
        }

        foreach (['numero', 'nom', 'name', 'username', 'designation', 'code'] as $champ) {
            if (! empty($model->{$champ})) {
                return (string) $model->{$champ};
            }
        }

        return '#'.$model->getKey();
    }
}
