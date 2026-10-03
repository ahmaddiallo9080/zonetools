<?php

namespace App\Http\Requests;

use App\Models\Forfait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ForfaitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // « 1 000 », « 1.000 » ou « 1000 » → 1000
        $nettoyer = fn ($v) => is_string($v) ? preg_replace('/[^\d]/', '', $v) : $v;

        $prixSites = collect($this->input('prix_sites', []))
            ->filter(fn ($ligne) => filled($ligne['site_id'] ?? null) || filled($ligne['prix'] ?? null))
            ->map(fn ($ligne) => ['site_id' => $ligne['site_id'] ?? null, 'prix' => $nettoyer($ligne['prix'] ?? null)])
            ->values()
            ->all();

        $this->merge([
            'code' => $this->filled('code') ? mb_strtoupper(trim($this->input('code'))) : null,
            'prix' => $nettoyer($this->input('prix')),
            'prix_sites' => $prixSites,
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('forfait')?->id;

        return [
            'code' => ['nullable', 'string', 'max:20', 'alpha_dash', Rule::unique('forfaits', 'code')->ignore($id)],
            'nom' => ['required', 'string', 'max:80', Rule::unique('forfaits', 'nom')->ignore($id)],
            'duree_valeur' => ['required', 'integer', 'min:1', 'max:10000'],
            'duree_unite' => ['required', Rule::in(array_keys(Forfait::UNITES))],
            'nb_appareils' => ['required', 'integer', 'min:1', 'max:50'],
            'prix' => ['required', 'integer', 'min:0', 'max:100000000'],
            'couleur' => ['required', Rule::in(array_keys(Forfait::COULEURS))],
            'description' => ['nullable', 'string', 'max:255'],
            'statut' => ['required', Rule::in(array_keys(Forfait::STATUTS))],
            'prix_sites' => ['array'],
            'prix_sites.*.site_id' => ['required', 'integer', 'distinct', 'exists:sites,id,deleted_at,NULL'],
            'prix_sites.*.prix' => ['required', 'integer', 'min:0', 'max:100000000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'duree_valeur' => 'durée',
            'duree_unite' => 'unité de durée',
            'nb_appareils' => "nombre d'appareils",
            'prix' => 'prix de base',
            'prix_sites.*.site_id' => 'site',
            'prix_sites.*.prix' => 'prix du site',
        ];
    }

    public function messages(): array
    {
        return [
            'prix_sites.*.site_id.distinct' => 'Ce site apparaît plusieurs fois dans les prix particuliers.',
        ];
    }

    /** Prix particuliers au format attendu par sync() : [site_id => ['prix' => x]] */
    public function prixParSite(): array
    {
        return collect($this->validated('prix_sites', []))
            ->mapWithKeys(fn ($ligne) => [(int) $ligne['site_id'] => ['prix' => (int) $ligne['prix']]])
            ->all();
    }
}
