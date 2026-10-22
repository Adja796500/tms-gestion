<?php

namespace App\Livewire;

use App\Models\Achat;
use App\Models\Fournisseur;
use App\Models\ReglementFournisseur;
use App\Support\NumeroGenerator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReglementsFournisseurs extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public string $date = '';
    public ?int $fournisseur_id = null;
    public ?int $achat_id = null;
    public string $montant = '0';
    public string $mode_paiement = 'Especes';
    public string $observations = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function creer(): void
    {
        abort_unless(auth()->user()->peutModifier('REGLEMENTS_FOURNISSEURS'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('REGLEMENTS_FOURNISSEURS'), 403);

        $data = $this->validate([
            'date' => 'required|date',
            'fournisseur_id' => 'required|existe_ici:fournisseurs',
            'achat_id' => 'nullable|existe_ici:achats',
            'montant' => 'required|numeric|min:0.01',
            'mode_paiement' => 'required|string|max:50',
            'observations' => 'nullable|string|max:255',
        ]);

        // Un reglement rattache a un achat doit concerner le bon fournisseur et ne pas
        // depasser ce qui reste a payer (sinon les dettes deviendraient negatives).
        if ($data['achat_id']) {
            $achat = Achat::findOrFail($data['achat_id']);
            $reste = round((float) $achat->total - (float) $achat->montant_regle, 2);

            if ((int) $achat->fournisseur_id !== (int) $data['fournisseur_id']) {
                $this->addError('achat_id', "Cet achat n'a pas été fait auprès de ce fournisseur.");

                return;
            }

            if ((float) $data['montant'] > $reste + 0.01) {
                $this->addError('montant', $reste <= 0
                    ? 'Cet achat est déjà entièrement réglé.'
                    : 'Le montant dépasse le reste à payer sur cet achat ('.number_format($reste, 0, ',', ' ').' '.config('app.currency').').');

                return;
            }
        }

        DB::transaction(function () use ($data) {
            $data['numero'] = NumeroGenerator::suivant('reglements_fournisseurs', 'REG', auth()->user()->entreprise_id);
            $data['achat_id'] = $data['achat_id'] ?: null;

            ReglementFournisseur::create($data);

            if ($data['achat_id']) {
                $achat = Achat::find($data['achat_id']);
                if ($achat) {
                    $achat->montant_regle = round((float) $achat->montant_regle + (float) $data['montant'], 2);
                    $achat->rafraichirStatut();
                }
            }
        });

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', 'Règlement fournisseur enregistré avec succès.');
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('REGLEMENTS_FOURNISSEURS'), 403);

        $reglement = ReglementFournisseur::findOrFail($id);

        // Symetrique de enregistrer() : le montant avait ete ajoute a l'achat rattache,
        // il faut l'en retirer, sinon l'achat resterait compte comme paye.
        DB::transaction(function () use ($reglement) {
            if ($reglement->achat_id && ($achat = Achat::find($reglement->achat_id))) {
                $achat->montant_regle = max(0, round((float) $achat->montant_regle - (float) $reglement->montant, 2));
                $achat->rafraichirStatut();
            }

            $reglement->delete();
        });

        session()->flash('status', 'Règlement supprimé.');
    }

    public function annuler(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['fournisseur_id', 'achat_id', 'montant', 'mode_paiement', 'observations']);
        $this->date = now()->toDateString();
        $this->montant = '0';
        $this->mode_paiement = 'Especes';
        $this->resetErrorBag();
    }

    public function render()
    {
        $reglements = ReglementFournisseur::query()
            ->with(['fournisseur', 'achat'])
            ->latest('date')
            ->latest('id')
            ->paginate(10);

        return view('livewire.reglements-fournisseurs', [
            'reglements' => $reglements,
            'fournisseursDisponibles' => Fournisseur::orderBy('nom')->get(),
            'achatsDisponibles' => Achat::orderBy('numero')->get(),
        ])->layout('layouts.app', ['title' => 'Règlements fournisseurs']);
    }
}
