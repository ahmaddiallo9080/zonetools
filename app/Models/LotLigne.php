<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotLigne extends Model
{
    protected $table = 'lot_lignes';

    protected $fillable = ['lot_id', 'forfait_id', 'quantite', 'prix_unitaire', 'montant'];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'prix_unitaire' => 'integer',
            'montant' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (LotLigne $ligne) => $ligne->montant = $ligne->quantite * $ligne->prix_unitaire);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function forfait(): BelongsTo
    {
        return $this->belongsTo(Forfait::class)->withTrashed();
    }
}
