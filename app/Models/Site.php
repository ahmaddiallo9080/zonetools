<?php

namespace App\Models;

use App\Models\Concerns\GenereCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use GenereCode, HasFactory, SoftDeletes;

    public const PREFIXE_CODE = 'SITE';

    public const STATUT_ACTIF = 'actif';
    public const STATUT_MAINTENANCE = 'maintenance';
    public const STATUT_INACTIF = 'inactif';

    /** Libellés et couleurs des statuts (utilisés dans les vues). */
    public const STATUTS = [
        self::STATUT_ACTIF => ['label' => 'Actif', 'color' => 'green'],
        self::STATUT_MAINTENANCE => ['label' => 'En maintenance', 'color' => 'amber'],
        self::STATUT_INACTIF => ['label' => 'Inactif', 'color' => 'gray'],
    ];

    protected $fillable = [
        'code', 'superviseur_id', 'nom', 'ville', 'quartier', 'adresse',
        'telephone', 'date_ouverture', 'statut', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_ouverture' => 'date',
        ];
    }

    /* ----------------------------------------------------------------
     |  Relations
     | ---------------------------------------------------------------- */

    public function superviseur(): BelongsTo
    {
        return $this->belongsTo(Superviseur::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    /** Forfaits ayant un prix particulier sur ce site. */
    public function forfaitsPrixParticuliers(): BelongsToMany
    {
        return $this->belongsToMany(Forfait::class)->withPivot('prix')->withTimestamps();
    }

    /* ----------------------------------------------------------------
     |  Scopes
     | ---------------------------------------------------------------- */

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_ACTIF);
    }

    public function scopeRecherche(Builder $query, ?string $terme): Builder
    {
        if (blank($terme)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($terme) {
            $q->where('nom', 'like', "%{$terme}%")
              ->orWhere('code', 'like', "%{$terme}%")
              ->orWhere('ville', 'like', "%{$terme}%")
              ->orWhere('quartier', 'like', "%{$terme}%")
              ->orWhere('telephone', 'like', "%{$terme}%");
        });
    }

    /* ----------------------------------------------------------------
     |  Accesseurs
     | ---------------------------------------------------------------- */

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut]['label'] ?? ucfirst((string) $this->statut);
    }

    public function getStatutColorAttribute(): string
    {
        return self::STATUTS[$this->statut]['color'] ?? 'gray';
    }

    public function getLocalisationAttribute(): ?string
    {
        $parts = array_filter([$this->quartier, $this->ville]);

        return $parts ? implode(', ', $parts) : null;
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class);
    }
}
