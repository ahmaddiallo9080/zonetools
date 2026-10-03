<?php

namespace App\Models;

use App\Models\Concerns\GenereCode;
use App\Support\Format;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Depense extends Model
{
    use GenereCode, HasFactory, SoftDeletes;

    public const PREFIXE_CODE = 'DEP';

    /** Catégories de dépenses d'un hotspot. */
    public const CATEGORIES = [
        'internet' => 'Abonnement internet',
        'electricite' => 'Électricité',
        'carburant' => 'Carburant (groupe électrogène)',
        'loyer' => 'Loyer / emplacement',
        'materiel' => 'Matériel & équipement',
        'maintenance' => 'Maintenance & réparation',
        'impression' => 'Impression des tickets',
        'transport' => 'Transport',
        'salaire' => 'Salaire / prime',
        'communication' => 'Crédit téléphone / communication',
        'taxes' => 'Taxes & frais administratifs',
        'autre' => 'Autre',
    ];

    protected $fillable = [
        'code', 'site_id', 'date_depense', 'categorie', 'libelle', 'montant',
        'mode', 'fournisseur', 'reference', 'justificatif', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_depense' => 'date',
            'montant' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class)->withTrashed();
    }

    public function getCategorieLabelAttribute(): string
    {
        return self::CATEGORIES[$this->categorie] ?? ucfirst((string) $this->categorie);
    }

    public function getModeLabelAttribute(): string
    {
        return Paiement::MODES[$this->mode] ?? $this->mode;
    }

    public function getMontantAfficheAttribute(): string
    {
        return Format::gnf($this->montant);
    }

    /** Nom du site, ou « Général » pour une dépense commune. */
    public function getSiteLabelAttribute(): string
    {
        return $this->site?->nom ?? 'Général (tous les sites)';
    }

    public function justificatifEstImage(): bool
    {
        return (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', (string) $this->justificatif);
    }
}
