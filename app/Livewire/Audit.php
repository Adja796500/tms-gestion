<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Feuille AUDIT du classeur : uniquement les modifications et suppressions, avec
 * le detail de la ligne avant (et apres, pour une modification). Les donnees
 * viennent du journal d'activite, alimente automatiquement par AuditObserver.
 */
class Audit extends Component
{
    use WithPagination;

    public string $action = '';
    public string $module = '';

    public function updating(string $name): void
    {
        if (in_array($name, ['action', 'module'], true)) {
            $this->resetPage();
        }
    }

    /**
     * "Champ: valeur | Champ: valeur", comme la colonne Detail du classeur.
     */
    public static function detail(?array $donnees): string
    {
        if (! $donnees) {
            return '';
        }

        return collect($donnees)
            ->except(['id', 'entreprise_id', 'created_at', 'updated_at'])
            ->map(fn ($valeur, $champ) => $champ.': '.(is_scalar($valeur) || $valeur === null ? (string) $valeur : json_encode($valeur)))
            ->implode(' | ');
    }

    public function render()
    {
        $requete = ActivityLog::query()->whereIn('action', ['modification', 'suppression']);

        return view('livewire.audit', [
            'lignes' => (clone $requete)
                ->when($this->action, fn ($q) => $q->where('action', $this->action))
                ->when($this->module, fn ($q) => $q->where('module', $this->module))
                ->latest('created_at')
                ->latest('id')
                ->paginate(25),
            'modules' => (clone $requete)->distinct()->orderBy('module')->pluck('module'),
        ])->layout('layouts.app', ['title' => 'Audit — modifications et suppressions']);
    }
}
