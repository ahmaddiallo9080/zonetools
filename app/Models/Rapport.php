<?php

namespace App\Models;

use App\Models\Concerns\GenereCode;
use App\Support\Format;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rapport extends Model
{
    use GenereCode, HasFactory;

    public const PREFIXE_CODE = 'RAP';

    protected $fillable = ['code', 'lot_id', 'date_rapport', 'montant_verse', 'observations'];

    protected function casts(): array
    {
        return [
            'date_rapport' => 'date',
            'quantite_vendue' => 'integer',
            'quantite_defectueuse' => 'integer',
            'quantite_rendue' => 'integer',
            'montant_vendu' => 'integer',
            'commission_agent' => 'integer',
            'commission_superviseur' => 'integer',
            'commission_agent_deduite' => 'boolean',
            'montant_attendu' => 'integer',
            'montant_verse' => 'integer',
            'ecart' => 'integer',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class)->withTrashed();
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(RapportLigne::class);
    }

    /**
     * Recalcule tous les totaux et montants à partir des lignes et des taux du lot.
     */
    public function recalculer(): void
    {
        $this->load(['lignes', 'lot']);

        $this->quantite_vendue = $this->lignes->sum('vendus');
        $this->quantite_defectueuse = $this->lignes->sum('defectueux');
        $this->quantite_rendue = $this->lignes->sum('rendus');
        $this->montant_vendu = $this->lignes->sum('montant_vendu');

        $this->commission_agent = (int) round($this->montant_vendu * (float) $this->lot->taux_agent / 100);
        $this->commission_superviseur = (int) round($this->montant_vendu * (float) $this->lot->taux_superviseur / 100);

        $this->montant_attendu = $this->montant_vendu - ($this->commission_agent_deduite ? $this->commission_agent : 0);
        $this->ecart = $this->montant_verse - $this->montant_attendu;

        $this->saveQuietly();
    }

    /* ----------------------------------------------------------------
     |  Affichage
     | ---------------------------------------------------------------- */

    public function getQuantiteRemiseAttribute(): int
    {
        return $this->quantite_vendue + $this->quantite_defectueuse + $this->quantite_rendue;
    }

    /** % de tickets défectueux sur la quantité remise. */
    public function getTauxDefectueuxAttribute(): float
    {
        return $this->quantite_remise ? round($this->quantite_defectueuse * 100 / $this->quantite_remise, 1) : 0;
    }

    /** Ce qui reste au gérant une fois toutes les commissions payées. */
    public function getNetGerantAttribute(): int
    {
        return $this->montant_vendu - $this->commission_agent - $this->commission_superviseur;
    }

    public function getEcartColorAttribute(): string
    {
        return $this->ecart < 0 ? 'red' : ($this->ecart > 0 ? 'amber' : 'green');
    }

    public function getEcartLabelAttribute(): string
    {
        if ($this->ecart === 0) {
            return 'Compte juste';
        }

        return ($this->ecart < 0 ? 'Manque ' : 'Surplus ') . Format::gnf(abs($this->ecart));
    }
}
