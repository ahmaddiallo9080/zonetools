<?php

namespace App\Models;

use App\Models\Concerns\GenereCode;
use App\Support\Format;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Forfait extends Model
{
    use GenereCode, HasFactory, SoftDeletes;

    public const PREFIXE_CODE = 'FOR';

    /** Unités de durée : libellés singulier / pluriel et équivalent en minutes. */
    public const UNITES = [
        'minute' => ['singulier' => 'minute', 'pluriel' => 'minutes', 'minutes' => 1],
        'heure' => ['singulier' => 'heure', 'pluriel' => 'heures', 'minutes' => 60],
        'jour' => ['singulier' => 'jour', 'pluriel' => 'jours', 'minutes' => 1440],
        'semaine' => ['singulier' => 'semaine', 'pluriel' => 'semaines', 'minutes' => 10080],
        'mois' => ['singulier' => 'mois', 'pluriel' => 'mois', 'minutes' => 43200], // 30 jours
    ];

    public const STATUTS = [
        'actif' => ['label' => 'Actif', 'color' => 'green'],
        'inactif' => ['label' => 'Inactif', 'color' => 'gray'],
    ];

    /** Couleurs disponibles pour repérer un forfait (badge). */
    public const COULEURS = [
        'primary' => 'Bleu',
        'green' => 'Vert',
        'amber' => 'Orange',
        'red' => 'Rouge',
        'gray' => 'Gris',
    ];

    protected $fillable = [
        'code', 'nom', 'duree_valeur', 'duree_unite', 'nb_appareils',
        'prix', 'couleur', 'description', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'duree_valeur' => 'integer',
            'duree_minutes' => 'integer',
            'nb_appareils' => 'integer',
            'prix' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Durée totale en minutes, recalculée à chaque enregistrement (tri par durée)
        static::saving(function (Forfait $forfait) {
            $forfait->duree_minutes = $forfait->duree_valeur * (self::UNITES[$forfait->duree_unite]['minutes'] ?? 1);
        });
    }

    /* ----------------------------------------------------------------
     |  Relations & prix
     | ---------------------------------------------------------------- */

    /** Sites ayant un prix particulier pour ce forfait. */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class)->withPivot('prix')->withTimestamps();
    }

    /** Lignes de lots utilisant ce forfait. */
    public function lotLignes(): HasMany
    {
        return $this->hasMany(LotLigne::class);
    }

    /** Prix appliqué sur un site : prix particulier s'il existe, sinon prix de base. */
    public function prixPour(Site|int|null $site): int
    {
        if ($site === null) {
            return $this->prix;
        }

        $siteId = $site instanceof Site ? $site->id : $site;

        $particulier = $this->relationLoaded('sites')
            ? $this->sites->firstWhere('id', $siteId)?->pivot->prix
            : $this->sites()->where('sites.id', $siteId)->value('forfait_site.prix');

        return (int) ($particulier ?? $this->prix);
    }

    /* ----------------------------------------------------------------
     |  Scopes
     | ---------------------------------------------------------------- */

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('statut', 'actif');
    }

    public function scopeRecherche(Builder $query, ?string $terme): Builder
    {
        if (blank($terme)) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('nom', 'like', "%{$terme}%")
            ->orWhere('code', 'like', "%{$terme}%")
            ->orWhere('description', 'like', "%{$terme}%"));
    }

    /* ----------------------------------------------------------------
     |  Accesseurs d'affichage
     | ---------------------------------------------------------------- */

    /** « 1 heure », « 7 jours »… */
    public function getDureeLabelAttribute(): string
    {
        $unite = self::UNITES[$this->duree_unite] ?? null;

        if (! $unite) {
            return (string) $this->duree_valeur;
        }

        return $this->duree_valeur . ' ' . ($this->duree_valeur > 1 ? $unite['pluriel'] : $unite['singulier']);
    }

    public function getAppareilsLabelAttribute(): string
    {
        return $this->nb_appareils . ' appareil' . ($this->nb_appareils > 1 ? 's' : '');
    }

    public function getPrixAfficheAttribute(): string
    {
        return Format::gnf($this->prix);
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut]['label'] ?? ucfirst((string) $this->statut);
    }

    public function getStatutColorAttribute(): string
    {
        return self::STATUTS[$this->statut]['color'] ?? 'gray';
    }
}
