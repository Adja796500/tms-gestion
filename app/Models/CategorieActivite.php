<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategorieActivite extends Model
{
    use AppartientAUneEntreprise;

    protected $table = 'categories_activite';

    protected $fillable = [
        'entreprise_id',
        'nom',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
