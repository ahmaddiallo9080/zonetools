<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Règles communes aux superviseurs et aux agents.
 */
abstract class PersonneRequest extends FormRequest
{
    /** Nom de la table (superviseurs / agents). */
    abstract protected function table(): string;

    /** Nom du paramètre de route (superviseur / agent). */
    abstract protected function parametreRoute(): string;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->filled('code') ? mb_strtoupper(trim($this->input('code'))) : null,
            'nom' => $this->filled('nom') ? mb_strtoupper(trim($this->input('nom'))) : $this->input('nom'),
            'prenom' => $this->filled('prenom') ? mb_convert_case(trim($this->input('prenom')), MB_CASE_TITLE) : $this->input('prenom'),
            'taux_commission' => $this->filled('taux_commission') ? str_replace(',', '.', $this->input('taux_commission')) : 0,
        ]);
    }

    public function rules(): array
    {
        $id = $this->route($this->parametreRoute())?->id;
        $telephone = ['string', 'max:30', 'regex:/^[0-9 +().-]+$/'];

        return [
            'code' => ['nullable', 'string', 'max:20', 'alpha_dash', Rule::unique($this->table(), 'code')->ignore($id)],
            'nom' => ['required', 'string', 'max:80'],
            'prenom' => ['required', 'string', 'max:80'],
            'telephone' => ['required', ...$telephone, Rule::unique($this->table(), 'telephone')->ignore($id)->withoutTrashed()],
            'telephone2' => ['nullable', ...$telephone],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'piece_identite' => ['nullable', 'string', 'max:60'],
            'date_embauche' => ['nullable', 'date', 'before_or_equal:today'],
            'taux_commission' => ['required', 'numeric', 'min:0', 'max:100'],
            'statut' => ['required', Rule::in(['actif', 'inactif'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'prenom' => 'prénom',
            'telephone' => 'téléphone',
            'telephone2' => 'second téléphone',
            'piece_identite' => "pièce d'identité",
            'date_embauche' => "date d'embauche",
            'taux_commission' => 'taux de commission',
            'site_id' => 'site',
            'sites' => 'sites',
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé par une autre personne.',
        ];
    }
}
