<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Génère automatiquement un code lisible (ex : SITE-001, SUP-001, AGT-001)
 * si aucun code n'est saisi (et conserve l'ancien code si le champ est vidé).
 * Le modèle doit définir la constante PREFIXE_CODE.
 */
trait GenereCode
{
    protected static function bootGenereCode(): void
    {
        // À la création comme à la modification : un code vidé reprend
        // l'ancien code, ou un nouveau code est généré.
        static::saving(function ($model) {
            if (blank($model->code)) {
                $model->code = $model->getOriginal('code') ?: static::prochainCode();
            }
        });
    }

    public static function prochainCode(): string
    {
        // On compte aussi les éléments supprimés (corbeille) pour ne jamais réutiliser un code
        $requete = fn () => in_array(SoftDeletes::class, class_uses_recursive(static::class), true)
            ? static::withTrashed()
            : static::query();

        $dernier = $requete()->max('id') ?? 0;

        do {
            $dernier++;
            $code = static::PREFIXE_CODE . '-' . str_pad((string) $dernier, 3, '0', STR_PAD_LEFT);
        } while ($requete()->where('code', $code)->exists());

        return $code;
    }
}
