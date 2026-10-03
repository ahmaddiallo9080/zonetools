<?php

namespace App\Models;

use App\Models\Concerns\GenereCode;
use App\Support\Format;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Paiement extends Model
{
    use GenereCode, HasFactory;

    public const PREFIXE_CODE = 'PAY';

    public const MODES = [
        'especes' => 'Espèces',
        'orange_money' => 'Orange Money',
        'mtn_money' => 'MTN Mobile Money',
        'virement' => 'Virement bancaire',
        'autre' => 'Autre',
    ];

    public const TYPES = [
        'superviseur' => 'Superviseur',
        'agent' => 'Agent',
    ];

    protected $fillable = [
        'code', 'beneficiaire_type', 'beneficiaire_id', 'date_paiement', 'montant',
        'mode', 'reference', 'periode_du', 'periode_au', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_paiement' => 'date',
            'periode_du' => 'date',
            'periode_au' => 'date',
            'montant' => 'integer',
        ];
    }

    /** Agent ou superviseur payé (même supprimé ensuite). */
    public function beneficiaire(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function getModeLabelAttribute(): string
    {
        return self::MODES[$this->mode] ?? $this->mode;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->beneficiaire_type] ?? $this->beneficiaire_type;
    }

    public function getMontantAfficheAttribute(): string
    {
        return Format::gnf($this->montant);
    }

    public function getPeriodeLabelAttribute(): ?string
    {
        if (! $this->periode_du && ! $this->periode_au) {
            return null;
        }

        return 'du ' . ($this->periode_du?->format('d/m/Y') ?? '…') . ' au ' . ($this->periode_au?->format('d/m/Y') ?? '…');
    }
}
