<?php

namespace App\Livewire;

use App\Models\AccessRule;
use App\Models\User;
use Livewire\Component;

class Parametres extends Component
{
    public function basculer(int $ruleId): void
    {
        abort_unless(auth()->user()->isAdministrateur(), 403);

        $regle = AccessRule::findOrFail($ruleId);

        if ($regle->role === 'Administrateur') {
            return;
        }

        $nouvelAcces = ! $regle->autorise;

        $regle->update([
            'autorise' => $nouvelAcces,
            // Sans acces au module, la modification n'a plus de sens.
            'peut_modifier' => $nouvelAcces ? $regle->peut_modifier : false,
        ]);

        session()->flash('status', "Droit d'accès '{$regle->module}' pour le profil '{$regle->role}' mis à jour.");
    }

    public function basculerModification(int $ruleId): void
    {
        abort_unless(auth()->user()->isAdministrateur(), 403);

        $regle = AccessRule::findOrFail($ruleId);

        if ($regle->role === 'Administrateur' || ! $regle->autorise) {
            return;
        }

        $regle->update(['peut_modifier' => ! $regle->peut_modifier]);

        session()->flash('status', "Droit de modification '{$regle->module}' pour le profil '{$regle->role}' mis à jour.");
    }

    public function render()
    {
        $modules = AccessRule::query()
            ->orderBy('module')
            ->get()
            ->groupBy('module');

        return view('livewire.parametres', [
            'modules' => $modules,
            'roles' => User::PROFILS,
        ])->layout('layouts.app', ['title' => 'Paramètres & droits d\'accès']);
    }
}
