<?php

namespace App\Livewire;

use App\Models\ParametreEntreprise;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class ParametresEntreprise extends Component
{
    use WithFileUploads;

    public ?int $parametreId = null;

    public string $nom_entreprise = '';
    public string $sigle = '';
    public ?string $logo_path = null;

    #[Validate('nullable|image|max:2048')]
    public $logo = null;

    public string $activites_texte = '';
    public string $telephone = '';
    public string $email = '';
    public string $ville = '';
    public string $adresse = '';
    public string $regime_fiscal = '';
    public string $division_fiscale = '';
    public string $rccm = '';
    public string $ifu = '';
    public string $signataire = '';
    public string $taux_investissement = '0';
    public string $taux_epargne = '0';
    public string $taux_loyer = '0';
    public string $taux_fonctionnement = '0';
    public string $solde_initial_caisse = '0';
    public string $solde_initial_banque = '0';

    public function mount(): void
    {
        $parametres = ParametreEntreprise::firstOrCreate(
            ['entreprise_id' => auth()->user()->entreprise_id],
            []
        );

        $this->parametreId = $parametres->id;
        $this->nom_entreprise = (string) $parametres->nom_entreprise;
        $this->sigle = (string) $parametres->sigle;
        $this->logo_path = $parametres->logo_path;
        $this->activites_texte = (string) $parametres->activites_texte;
        $this->telephone = (string) $parametres->telephone;
        $this->email = (string) $parametres->email;
        $this->ville = (string) $parametres->ville;
        $this->adresse = (string) $parametres->adresse;
        $this->regime_fiscal = (string) $parametres->regime_fiscal;
        $this->division_fiscale = (string) $parametres->division_fiscale;
        $this->rccm = (string) $parametres->rccm;
        $this->ifu = (string) $parametres->ifu;
        $this->signataire = (string) $parametres->signataire;
        $this->taux_investissement = (string) $parametres->taux_investissement;
        $this->taux_epargne = (string) $parametres->taux_epargne;
        $this->taux_loyer = (string) $parametres->taux_loyer;
        $this->taux_fonctionnement = (string) $parametres->taux_fonctionnement;
        $this->solde_initial_caisse = (string) $parametres->solde_initial_caisse;
        $this->solde_initial_banque = (string) $parametres->solde_initial_banque;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->peutModifier('CONFIGURATION_ENTREPRISE'), 403);

        $data = $this->validate([
            'nom_entreprise' => 'nullable|string|max:255',
            'sigle' => 'nullable|string|max:20',
            'logo' => 'nullable|image|max:2048',
            'activites_texte' => 'nullable|string',
            'telephone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'ville' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:255',
            'regime_fiscal' => 'nullable|string|max:255',
            'division_fiscale' => 'nullable|string|max:255',
            'rccm' => 'nullable|string|max:255',
            'ifu' => 'nullable|string|max:255',
            'signataire' => 'nullable|string|max:255',
            'taux_investissement' => 'required|numeric|min:0|max:100',
            'taux_epargne' => 'required|numeric|min:0|max:100',
            'taux_loyer' => 'required|numeric|min:0|max:100',
            'taux_fonctionnement' => ['required', 'numeric', 'min:0', 'max:100', $this->regleSommeDesTaux()],
            'solde_initial_caisse' => 'required|numeric|min:0',
            'solde_initial_banque' => 'required|numeric|min:0',
        ]);

        if ($this->logo) {
            if ($this->logo_path) {
                Storage::disk('public')->delete($this->logo_path);
            }

            $data['logo_path'] = $this->logo->store('logos', 'public');
        }
        unset($data['logo']);

        ParametreEntreprise::where('id', $this->parametreId)->update($data);

        $this->logo = null;
        $this->logo_path = $data['logo_path'] ?? $this->logo_path;

        session()->flash('status', 'Paramètres entreprise enregistrés avec succès.');
    }

    /**
     * Regle metier explicitement demandee par le cahier des charges (§4.3) : la somme
     * des 4 taux de repartition doit toujours faire 100, alors qu'Excel ne le
     * signalait qu'en avertissement visuel. Verifiee ici sur le total des 4 champs,
     * portee par le dernier d'entre eux dans la liste de validation.
     */
    private function regleSommeDesTaux(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $total = (float) $this->taux_investissement
                + (float) $this->taux_epargne
                + (float) $this->taux_loyer
                + (float) $value;

            if (abs($total - 100) > 0.01) {
                $fail("La somme des 4 taux doit etre egale a 100 (actuellement {$total}).");
            }
        };
    }

    public function render()
    {
        return view('livewire.parametres-entreprise')
            ->layout('layouts.app', ['title' => 'Configuration entreprise']);
    }
}
