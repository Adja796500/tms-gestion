<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Livewire\Component;

/**
 * Bouton "Changer le mot de passe" de la feuille ACCUEIL : chaque utilisateur
 * change le sien (ancien mot de passe, nouveau d'au moins 8 caracteres, confirmation).
 */
class MotDePasse extends Component
{
    public string $actuel = '';
    public string $nouveau = '';
    public string $nouveau_confirmation = '';

    public function enregistrer(): void
    {
        $this->validate([
            'actuel' => 'required|string',
            'nouveau' => 'required|string|min:8|confirmed|different:actuel',
        ], [
            'nouveau.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'nouveau.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
            'nouveau.different' => "Le nouveau mot de passe doit être différent de l'actuel.",
        ]);

        $user = auth()->user();

        if (! Hash::check($this->actuel, $user->password)) {
            $this->addError('actuel', 'Mot de passe actuel incorrect.');

            return;
        }

        $user->forceFill(['password' => Hash::make($this->nouveau)])->save();

        $this->reset(['actuel', 'nouveau', 'nouveau_confirmation']);
        session()->flash('status', 'Mot de passe modifié avec succès.');
    }

    public function render()
    {
        $layout = auth()->user()->est_super_administrateur ? 'layouts.super-admin' : 'layouts.app';

        return view('livewire.mot-de-passe')->layout($layout, ['title' => 'Changer le mot de passe']);
    }
}
