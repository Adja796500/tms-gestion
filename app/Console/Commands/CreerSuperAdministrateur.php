<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreerSuperAdministrateur extends Command
{
    protected $signature = 'superadmin:creer';

    protected $description = "Cree un compte super-administrateur (gere la liste des entreprises clientes, n'appartient a aucune entreprise)";

    public function handle(): int
    {
        $name = $this->ask('Nom complet');
        $username = $this->ask("Nom d'utilisateur");
        $password = $this->secret('Mot de passe (min. 8 caracteres)');

        $validateur = Validator::make(
            ['name' => $name, 'username' => $username, 'password' => $password],
            [
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:100|unique:users,username',
                'password' => 'required|string|min:8',
            ]
        );

        if ($validateur->fails()) {
            foreach ($validateur->errors()->all() as $erreur) {
                $this->error($erreur);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'username' => $username,
            'password' => Hash::make($password),
            'profil' => 'Super Administrateur',
            'actif' => true,
            'est_super_administrateur' => true,
            'entreprise_id' => null,
        ]);

        $this->info("Super-administrateur '{$username}' cree avec succes.");

        return self::SUCCESS;
    }
}
