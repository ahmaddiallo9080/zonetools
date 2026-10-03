<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bilan extends Model
{
    /** Champs chiffrés recopiés depuis le calcul (BilanMensuel::calculer). */
    public const CHIFFRES = [
        'rapports', 'lots_remis', 'tickets_remis', 'vendus', 'defectueux', 'rendus',
        'ventes', 'commission_agents', 'commission_superviseur', 'depenses',
        'benefice', 'montant_verse', 'manquants',
    ];

    protected $fillable = ['site_id', 'mois', 'details', 'observations', 'cloture_le', ...self::CHIFFRES];

    protected function casts(): array
    {
        return [
            'mois' => 'date',
            'details' => 'array',
            'cloture_le' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class)->withTrashed();
    }
}
