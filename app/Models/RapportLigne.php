<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RapportLigne extends Model
{
    protected $table = 'rapport_lignes';

    protected $fillable = [
        'rapport_id', 'lot_ligne_id', 'forfait_id', 'quantite_remise',
        'vendus', 'defectueux', 'rendus', 'prix_unitaire', 'montant_vendu',
    ];

    protected function casts(): array
    {
        return [
            'quantite_remise' => 'integer', 'vendus' => 'integer', 'defectueux' => 'integer',
            'rendus' => 'integer', 'prix_unitaire' => 'integer', 'montant_vendu' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (RapportLigne $ligne) {
            $ligne->rendus = $ligne->quantite_remise - $ligne->vendus - $ligne->defectueux;
            $ligne->montant_vendu = $ligne->vendus * $ligne->prix_unitaire;
        });
    }

    public function rapport(): BelongsTo
    {
        return $this->belongsTo(Rapport::class);
    }

    public function forfait(): BelongsTo
    {
        return $this->belongsTo(Forfait::class)->withTrashed();
    }
}
