<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class JournalActivite extends Component
{
    use WithPagination;

    public string $action = '';
    public string $module = '';
    public string $utilisateur = '';
    public string $dateDebut = '';
    public string $dateFin = '';

    public function updatingAction(): void
    {
        $this->resetPage();
    }

    public function updatingModule(): void
    {
        $this->resetPage();
    }

    public function updatingUtilisateur(): void
    {
        $this->resetPage();
    }

    public function updatingDateDebut(): void
    {
        $this->resetPage();
    }

    public function updatingDateFin(): void
    {
        $this->resetPage();
    }

    public function reinitialiser(): void
    {
        $this->reset(['action', 'module', 'utilisateur', 'dateDebut', 'dateFin']);
        $this->resetPage();
    }

    public function render()
    {
        $logs = ActivityLog::query()
            ->when($this->action, fn ($q) => $q->where('action', $this->action))
            ->when($this->module, fn ($q) => $q->where('module', $this->module))
            ->when($this->utilisateur, fn ($q) => $q->where('utilisateur_nom', 'like', "%{$this->utilisateur}%"))
            ->when($this->dateDebut, fn ($q) => $q->whereDate('created_at', '>=', $this->dateDebut))
            ->when($this->dateFin, fn ($q) => $q->whereDate('created_at', '<=', $this->dateFin))
            ->latest('created_at')
            ->paginate(25);

        return view('livewire.journal-activite', [
            'logs' => $logs,
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
            'modules' => ActivityLog::query()->distinct()->orderBy('module')->pluck('module'),
        ])->layout('layouts.app', ['title' => "Journal d'activité"]);
    }
}
