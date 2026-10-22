<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class Utilisateurs extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $profil = 'Consultation';
    public bool $actif = true;
    public string $password = '';

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier('UTILISATEURS'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function modifier(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('UTILISATEURS'), 403);

        $user = User::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = (string) $user->email;
        $this->profil = $user->profil;
        $this->actif = $user->actif;
        $this->password = '';
        $this->showForm = true;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('UTILISATEURS'), 403);

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username,'.($this->editingId ?? 'NULL').',id',
            'email' => 'nullable|email|max:255',
            'profil' => 'required|in:'.implode(',', User::PROFILS),
            'actif' => 'boolean',
            'password' => ($this->editingId ? 'nullable' : 'required').'|string|min:8',
        ]);

        $user = User::updateOrCreate(['id' => $this->editingId, 'entreprise_id' => auth()->user()->entreprise_id], [
            'entreprise_id' => auth()->user()->entreprise_id,
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?: null,
            'profil' => $data['profil'],
            'actif' => $data['actif'],
        ]);

        if (! empty($data['password'])) {
            $user->forceFill(['password' => Hash::make($data['password'])])->save();
        }

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', "Utilisateur {$user->username} enregistré avec succès.");
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('UTILISATEURS'), 403);

        if ($id === auth()->id()) {
            session()->flash('status', 'Vous ne pouvez pas supprimer votre propre compte.');

            return;
        }

        User::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id)->delete();
        session()->flash('status', 'Utilisateur supprimé.');
    }

    public function annuler(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'username', 'email', 'password']);
        $this->profil = 'Consultation';
        $this->actif = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.utilisateurs', [
            'utilisateurs' => User::where('entreprise_id', auth()->user()->entreprise_id)->orderBy('name')->paginate(15),
            'profils' => User::PROFILS,
        ])->layout('layouts.app', ['title' => 'Utilisateurs']);
    }
}
