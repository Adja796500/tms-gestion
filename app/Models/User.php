<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Pas de trait AppartientAUneEntreprise ici : resoudre l'utilisateur connecte
     * (auth()->user()) interroge deja ce modele, et le global scope du trait
     * appelle lui-meme auth()->user() -> boucle infinie. Le filtrage par
     * entreprise pour ce modele est fait explicitement dans Utilisateurs.php.
     */
    public const PROFILS = [
        'Administrateur',
        'Directeur',
        'Comptable',
        'Caissier',
        'Magasinier',
        'Commercial',
        'Consultation',
    ];

    protected $fillable = [
        'entreprise_id',
        'name',
        'username',
        'email',
        'password',
        'profil',
        'actif',
        'est_super_administrateur',
        'derniere_connexion',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'est_super_administrateur' => 'boolean',
            'derniere_connexion' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function isAdministrateur(): bool
    {
        return $this->profil === 'Administrateur';
    }

    public function peutAccederA(string $module): bool
    {
        // Le super-administrateur gere les entreprises, pas leurs donnees : il n'est
        // rattache a aucune et verrait sinon celles de toutes les entreprises melangees.
        if ($this->est_super_administrateur) {
            return false;
        }

        if ($this->profil === 'Administrateur') {
            return true;
        }

        // "VENTES|VENTE_RAPIDE" : l'un OU l'autre des modules suffit.
        return AccessRule::query()
            ->whereIn('module', explode('|', $module))
            ->where('role', $this->profil)
            ->where('autorise', true)
            ->exists();
    }

    /**
     * Contrairement a peutAccederA() qui autorise juste a consulter un module,
     * peutModifier() indique si le profil peut y creer/modifier/supprimer des donnees.
     */
    public function peutModifier(string $module): bool
    {
        if ($this->est_super_administrateur) {
            return false;
        }

        if ($this->profil === 'Administrateur') {
            return true;
        }

        return AccessRule::query()
            ->whereIn('module', explode('|', $module))
            ->where('role', $this->profil)
            ->where('autorise', true)
            ->where('peut_modifier', true)
            ->exists();
    }
}
