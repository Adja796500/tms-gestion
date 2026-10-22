<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenteLigne extends Model
{
    protected $table = 'vente_lignes';

    protected $fillable = [
        'vente_id',
        'article_id',
        'quantite',
        'prix_unitaire',
        'total',
    ];

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
