<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;

class Clients extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;
    public bool $showForm = false;

    public string $nom = '';
    public string $telephone = '';
    public string $email = '';
    public string $adresse = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier('CLIENTS'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function modifier(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('CLIENTS'), 403);

        $client = Client::findOrFail($id);
        $this->editingId = $client->id;
        $this->nom = $client->nom;
        $this->telephone = (string) $client->telephone;
        $this->email = (string) $client->email;
        $this->adresse = (string) $client->adresse;
        $this->showForm = true;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('CLIENTS'), 403);

        $data = $this->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:255',
        ]);

        $client = Client::updateOrCreate(['id' => $this->editingId], $data);

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', "Client {$client->nom} enregistré avec succès.");
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('CLIENTS'), 403);

        Client::findOrFail($id)->delete();
        session()->flash('status', 'Client supprimé.');
    }

    public function annuler(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'nom', 'telephone', 'email', 'adresse']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $clients = Client::query()
            ->when($this->search, fn ($q) => $q->where('nom', 'ilike', "%{$this->search}%"))
            ->withCount('ventes')
            ->orderBy('nom')
            ->paginate(10);

        return view('livewire.clients', ['clients' => $clients])
            ->layout('layouts.app', ['title' => 'Clients']);
    }
}
