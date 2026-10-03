<?php

namespace App\Models;

use App\Models\Concerns\EstUnePersonne;
use App\Models\Concerns\GenereCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Superviseur extends Model
{
    use EstUnePersonne, GenereCode, HasFactory, SoftDeletes;

    public const PREFIXE_CODE = 'SUP';

    protected $table = 'superviseurs';

    protected $fillable = [
        'code', 'nom', 'prenom', 'telephone', 'telephone2', 'email', 'adresse',
        'piece_identite', 'date_embauche', 'taux_commission', 'statut', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_embauche' => 'date',
            'taux_commission' => 'decimal:2',
        ];
    }

    /** Sites supervisés. */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    /** Agents de tous les sites supervisés. */
    public function agents(): HasManyThrough
    {
        return $this->hasManyThrough(Agent::class, Site::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }
}
