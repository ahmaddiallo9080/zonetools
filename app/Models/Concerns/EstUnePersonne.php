<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Comportements communs aux superviseurs et aux agents.
 */
trait EstUnePersonne
{
    public static function statuts(): array
    {
        return [
            'actif' => ['label' => 'Actif', 'color' => 'green'],
            'inactif' => ['label' => 'Inactif', 'color' => 'gray'],
        ];
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('statut', 'actif');
    }

    public function scopeRecherche(Builder $query, ?string $terme): Builder
    {
        if (blank($terme)) {
            return $query;
        }

        // Recherche aussi sur « Prénom Nom » (syntaxe différente pour SQLite, utilisé dans les tests)
        $nomComplet = $query->getConnection()->getDriverName() === 'sqlite'
            ? "prenom || ' ' || nom"
            : "CONCAT(prenom, ' ', nom)";

        return $query->where(function (Builder $q) use ($terme, $nomComplet) {
            $q->where('nom', 'like', "%{$terme}%")
              ->orWhere('prenom', 'like', "%{$terme}%")
              ->orWhere('code', 'like', "%{$terme}%")
              ->orWhere('telephone', 'like', "%{$terme}%")
              ->orWhereRaw("{$nomComplet} LIKE ?", ["%{$terme}%"]);
        });
    }

    public function getNomCompletAttribute(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }

    public function getInitialesAttribute(): string
    {
        return mb_strtoupper(mb_substr((string) $this->prenom, 0, 1) . mb_substr((string) $this->nom, 0, 1));
    }

    public function getStatutLabelAttribute(): string
    {
        return static::statuts()[$this->statut]['label'] ?? ucfirst((string) $this->statut);
    }

    public function getStatutColorAttribute(): string
    {
        return static::statuts()[$this->statut]['color'] ?? 'gray';
    }

    /** Taux formaté pour l'affichage : 10 %, 7,5 %... */
    public function getTauxAfficheAttribute(): string
    {
        return \App\Support\Format::pourcent($this->taux_commission);
    }
}
