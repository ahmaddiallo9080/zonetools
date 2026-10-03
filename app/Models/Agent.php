<?php

namespace App\Models;

use App\Models\Concerns\EstUnePersonne;
use App\Models\Concerns\GenereCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use EstUnePersonne, GenereCode, HasFactory, SoftDeletes;

    public const PREFIXE_CODE = 'AGT';

    protected $fillable = [
        'code', 'site_id', 'nom', 'prenom', 'telephone', 'telephone2', 'email', 'adresse',
        'piece_identite', 'date_embauche', 'taux_commission', 'statut', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_embauche' => 'date',
            'taux_commission' => 'decimal:2',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** Le superviseur de l'agent est celui de son site. */
    public function getSuperviseurAttribute(): ?Superviseur
    {
        return $this->site?->superviseur;
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }
}
