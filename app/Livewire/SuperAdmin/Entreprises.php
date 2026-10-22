<?php

namespace App\Livewire\SuperAdmin;

use App\Models\ActivityLog;
use App\Models\CategorieActivite;
use App\Models\Entreprise;
use App\Models\ParametreEntreprise;
use App\Models\User;
use Database\Seeders\AccessRuleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class Entreprises extends Component
{
    use WithPagination;

    /**
     * Valeurs de depart d'une nouvelle entreprise, reprises du classeur (feuille
     * PARAMETRES) : repartition du benefice et categories d'activite.
     */
    private const TAUX_PAR_DEFAUT = ['taux_investissement' => 55, 'taux_epargne' => 10, 'taux_loyer' => 5, 'taux_fonctionnement' => 30];

    private const CATEGORIES_PAR_DEFAUT = ['ABA', 'Photographie', 'Infographie', 'Caligraphie', 'Sérigraphie', 'Vente', 'Autres'];

    public bool $showForm = false;

    public string $nomEntreprise = '';
    public string $adminName = '';
    public string $adminUsername = '';
    public string $adminPassword = '';

    // Renommer une entreprise
    public ?int $renommageId = null;
    public string $nouveauNom = '';

    // Reinitialiser le mot de passe d'un utilisateur d'une entreprise
    public ?int $motDePasseEntrepriseId = null;
    public ?int $utilisateurId = null;
    public string $nouveauMotDePasse = '';

    public function creer(): void
    {
        $this->reset(['nomEntreprise', 'adminName', 'adminUsername', 'adminPassword']);
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function annuler(): void
    {
        $this->showForm = false;
    }

    public function enregistrer(): void
    {
        $data = $this->validate([
            'nomEntreprise' => 'required|string|max:255|unique:entreprises,nom',
            'adminName' => 'required|string|max:255',
            'adminUsername' => 'required|string|max:100|unique:users,username',
            'adminPassword' => 'required|string|min:8',
        ], [
            'nomEntreprise.unique' => 'Une entreprise porte déjà ce nom.',
            'adminUsername.unique' => "Ce nom d'utilisateur est déjà pris (il doit être unique sur toute la plateforme).",
        ]);

        DB::transaction(function () use ($data) {
            $entreprise = Entreprise::create([
                'nom' => $data['nomEntreprise'],
                'actif' => true,
            ]);

            User::create([
                'entreprise_id' => $entreprise->id,
                'name' => $data['adminName'],
                'username' => $data['adminUsername'],
                'password' => Hash::make($data['adminPassword']),
                'profil' => 'Administrateur',
                'actif' => true,
            ]);

            (new AccessRuleSeeder())->run($entreprise->id);

            // Fiche entreprise prete a completer (logo, coordonnees...) : sans elle, la
            // nouvelle entreprise n'aurait ni nom sur ses documents ni repartition du benefice.
            ParametreEntreprise::withoutGlobalScopes()->create(['entreprise_id' => $entreprise->id, 'nom_entreprise' => $entreprise->nom] + self::TAUX_PAR_DEFAUT);

            foreach (self::CATEGORIES_PAR_DEFAUT as $nom) {
                CategorieActivite::withoutGlobalScopes()->create(['entreprise_id' => $entreprise->id, 'nom' => $nom, 'actif' => true]);
            }
        });

        $this->showForm = false;
        session()->flash('status', "Entreprise « {$data['nomEntreprise']} » créée. Son administrateur peut se connecter avec « {$data['adminUsername']} ».");
    }

    public function ouvrirRenommage(int $id): void
    {
        $this->renommageId = $id;
        $this->nouveauNom = (string) Entreprise::findOrFail($id)->nom;
        $this->resetErrorBag();
    }

    public function renommer(): void
    {
        $this->validate(['nouveauNom' => 'required|string|max:255|unique:entreprises,nom,'.$this->renommageId]);

        $entreprise = Entreprise::findOrFail($this->renommageId);
        $entreprise->update(['nom' => $this->nouveauNom]);

        $this->renommageId = null;
        session()->flash('status', "Entreprise renommée en « {$entreprise->nom} ».");
    }

    public function ouvrirMotDePasse(int $entrepriseId): void
    {
        $this->motDePasseEntrepriseId = $entrepriseId;
        $this->utilisateurId = User::withoutGlobalScopes()->where('entreprise_id', $entrepriseId)->where('profil', 'Administrateur')->value('id');
        $this->nouveauMotDePasse = '';
        $this->resetErrorBag();
    }

    /**
     * Quand un client a perdu le mot de passe de son compte : le super-administrateur
     * en fixe un nouveau, a communiquer a la personne (qui le changera ensuite).
     */
    public function reinitialiserMotDePasse(): void
    {
        $this->validate([
            'utilisateurId' => 'required|integer',
            'nouveauMotDePasse' => 'required|string|min:8',
        ], ['nouveauMotDePasse.min' => 'Le mot de passe doit contenir au moins 8 caractères.']);

        $utilisateur = User::withoutGlobalScopes()
            ->where('entreprise_id', $this->motDePasseEntrepriseId)
            ->findOrFail($this->utilisateurId);

        $utilisateur->forceFill(['password' => Hash::make($this->nouveauMotDePasse)])->save();
        ActivityLog::enregistrer('reinitialisation_mot_de_passe', 'User', $utilisateur->id, null, null, $utilisateur->username);

        $this->motDePasseEntrepriseId = null;
        $this->nouveauMotDePasse = '';
        session()->flash('status', "Mot de passe de « {$utilisateur->username} » réinitialisé. Communiquez-le à la personne : elle pourra le changer depuis « Changer le mot de passe ».");
    }

    public function fermer(): void
    {
        $this->renommageId = null;
        $this->motDePasseEntrepriseId = null;
    }

    public function basculerActif(int $entrepriseId): void
    {
        $entreprise = Entreprise::findOrFail($entrepriseId);
        $entreprise->update(['actif' => ! $entreprise->actif]);

        session()->flash('status', "Statut de « {$entreprise->nom} » mis à jour.");
    }

    public function render()
    {
        return view('livewire.super-admin.entreprises', [
            'entreprises' => Entreprise::withCount('users')->orderBy('nom')->paginate(15),
            'utilisateursEntreprise' => $this->motDePasseEntrepriseId
                ? User::withoutGlobalScopes()->where('entreprise_id', $this->motDePasseEntrepriseId)->orderBy('username')->get(['id', 'name', 'username', 'profil'])
                : collect(),
            'entrepriseMotDePasse' => $this->motDePasseEntrepriseId ? Entreprise::find($this->motDePasseEntrepriseId) : null,
        ])->layout('layouts.super-admin', ['title' => 'Entreprises']);
    }
}
