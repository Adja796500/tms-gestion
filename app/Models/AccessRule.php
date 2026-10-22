<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Model;

class AccessRule extends Model
{
    use AppartientAUneEntreprise;

    protected $fillable = [
        'entreprise_id',
        'module',
        'module_label',
        'role',
        'autorise',
        'peut_modifier',
    ];

    protected function casts(): array
    {
        return [
            'autorise' => 'boolean',
            'peut_modifier' => 'boolean',
        ];
    }
}
