<?php

namespace App\Models;

use App\Models\Concerns\GenereCode;
use App\Support\Format;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lot extends Model
{
    use GenereCode, HasFactory, SoftDeletes;

    public const PREFIXE_CODE = 'LOT';

    public const EN_COURS = 'en_cours';
    public const TERMINE = 'termine';
    public const ANNULE = 'annule';

    public const STATUTS = [
        self::EN_COURS => ['label' => 'En cours', 'color' => 'primary'],
        self::TERMINE => ['label' => 'Terminé', 'color' => 'green'],
        self::ANNULE => ['label' => 'Annulé', 'color' => 'gray'],
    ];

    protected $fillable = [
        'code', 'site_id', 'agent_id', 'superviseur_id', 'date_remise', 'date_fin_prevue',
        'taux_agent', 'taux_superviseur', 'statut', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_remise' => 'date',
            'date_fin_prevue' => 'date',
            'taux_agent' => 'decimal:2',
            'taux_superviseur' => 'decimal:2',
            'quantite_totale' => 'integer',
            'montant_total' => 'integer',
        ];
    }

    /* ----------------------------------------------------------------
     |  Relations (withTrashed : un lot garde l'historique même si
     |  le site / l'agent / le superviseur a été supprimé ensuite)
     | ---------------------------------------------------------------- */

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class)->withTrashed();
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class)->withTrashed();
    }

    public function superviseur(): BelongsTo
    {
        return $this->belongsTo(Superviseur::class)->withTrashed();
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LotLigne::class);
    }

    public function rapport(): HasOne
    {
        return $this->hasOne(Rapport::class);
    }

    /* ----------------------------------------------------------------
     |  Calculs
     | ---------------------------------------------------------------- */

    /** Recalcule les totaux à partir des lignes. */
    public function recalculerTotaux(): void
    {
        $this->load('lignes');
        $this->quantite_totale = $this->lignes->sum('quantite');
        $this->montant_total = $this->lignes->sum('montant');
        $this->saveQuietly();
    }

    /** Commission agent si tous les tickets sont vendus. */
    public function getCommissionAgentPrevueAttribute(): int
    {
        return (int) round($this->montant_total * (float) $this->taux_agent / 100);
    }

    /** Commission superviseur si tous les tickets sont vendus. */
    public function getCommissionSuperviseurPrevueAttribute(): int
    {
        return (int) round($this->montant_total * (float) $this->taux_superviseur / 100);
    }

    /** Ce qui reste au gérant si tout est vendu. */
    public function getNetGerantPrevuAttribute(): int
    {
        return $this->montant_total - $this->commission_agent_prevue - $this->commission_superviseur_prevue;
    }

    public function estModifiable(): bool
    {
        return $this->statut === self::EN_COURS;
    }

    public function estEnRetard(): bool
    {
        return $this->statut === self::EN_COURS && $this->date_fin_prevue && $this->date_fin_prevue->isPast() && ! $this->date_fin_prevue->isToday();
    }

    /* ----------------------------------------------------------------
     |  Scopes
     | ---------------------------------------------------------------- */

    public function scopeEnCours(Builder $query): Builder
    {
        return $query->where('statut', self::EN_COURS);
    }

    /* ----------------------------------------------------------------
     |  Affichage
     | ---------------------------------------------------------------- */

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut]['label'] ?? $this->statut;
    }

    public function getStatutColorAttribute(): string
    {
        return self::STATUTS[$this->statut]['color'] ?? 'gray';
    }

    public function getMontantAfficheAttribute(): string
    {
        return Format::gnf($this->montant_total);
    }
}
