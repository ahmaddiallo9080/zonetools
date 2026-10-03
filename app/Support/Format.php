<?php

namespace App\Support;

/**
 * Formatage des montants et pourcentages pour l'affichage.
 */
class Format
{
    /** 15000 → « 15 000 GNF » */
    public static function gnf(int|float|string|null $montant, bool $devise = true): string
    {
        $texte = number_format((float) $montant, 0, ',', ' ');

        return $devise ? $texte . ' GNF' : $texte;
    }

    /** 7.50 → « 7,5 % » */
    public static function pourcent(int|float|string|null $taux): string
    {
        return rtrim(rtrim(number_format((float) $taux, 2, ',', ' '), '0'), ',') . ' %';
    }
}
