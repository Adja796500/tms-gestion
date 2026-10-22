<?php

namespace App\Livewire;

use App\Support\ModuleCatalogue;
use Livewire\Component;

class MonRole extends Component
{
    public function render()
    {
        $user = auth()->user();

        $modules = collect(ModuleCatalogue::MODULES)->map(function (array $infos, string $code) use ($user) {
            return [
                'code' => $code,
                'label' => $infos['label'],
                'description' => $infos['description'],
                'accessible' => $user->peutAccederA($code),
                'modifiable' => $user->peutModifier($code),
            ];
        });

        return view('livewire.mon-role', [
            'descriptionProfil' => ModuleCatalogue::PROFILS[$user->profil] ?? '',
            'modulesAccessibles' => $modules->filter(fn ($m) => $m['accessible'])->values(),
            'modulesInaccessibles' => $modules->reject(fn ($m) => $m['accessible'])->values(),
        ])->layout('layouts.app', ['title' => 'Mon rôle']);
    }
}
