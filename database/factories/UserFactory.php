<?php

namespace Database\Factories;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => Entreprise::factory(),
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'profil' => fake()->randomElement(User::PROFILS),
            'actif' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
