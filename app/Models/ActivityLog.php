<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use AppartientAUneEntreprise;

    public const UPDATED_AT = null;

    protected $table = 'activity_logs';

    protected $fillable = [
        'entreprise_id',
        'user_id',
        'utilisateur_nom',
        'utilisateur_profil',
        'action',
        'module',
        'libelle',
        'sujet_id',
        'donnees_avant',
        'donnees_apres',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'donnees_avant' => 'array',
            'donnees_apres' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function enregistrer(
        string $action,
        string $module,
        ?int $sujetId = null,
        ?array $avant = null,
        ?array $apres = null,
        ?string $libelle = null,
    ): void {
        $user = auth()->user();

        static::create([
            'user_id' => $user?->id,
            'utilisateur_nom' => $user?->username,
            'utilisateur_profil' => $user?->profil,
            'action' => $action,
            'module' => $module,
            'libelle' => $libelle,
            'sujet_id' => $sujetId,
            'donnees_avant' => $avant,
            'donnees_apres' => $apres,
            'ip' => request()?->ip(),
        ]);
    }
}
