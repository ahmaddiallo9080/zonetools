<?php

namespace App\Http\Requests;

use App\Models\Depense;
use App\Models\Paiement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->filled('code') ? mb_strtoupper(trim($this->input('code'))) : null,
            'site_id' => $this->input('site_id') === 'general' ? null : $this->input('site_id'),
            'montant' => is_string($this->input('montant')) ? preg_replace('/[^\d]/', '', $this->input('montant')) : $this->input('montant'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('depense')?->id;

        return [
            'code' => ['nullable', 'string', 'max:20', 'alpha_dash', "unique:depenses,code,{$id}"],
            'site_id' => ['nullable', 'integer', 'exists:sites,id,deleted_at,NULL'],
            'date_depense' => ['required', 'date', 'before_or_equal:today'],
            'categorie' => ['required', Rule::in(array_keys(Depense::CATEGORIES))],
            'libelle' => ['required', 'string', 'max:150'],
            'montant' => ['required', 'integer', 'min:1', 'max:10000000000'],
            'mode' => ['required', Rule::in(array_keys(Paiement::MODES))],
            'fournisseur' => ['nullable', 'string', 'max:120'],
            'reference' => ['nullable', 'string', 'max:80'],
            'fichier' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'supprimer_justificatif' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'site_id' => 'site',
            'date_depense' => 'date de la dépense',
            'categorie' => 'catégorie',
            'libelle' => 'libellé',
            'mode' => 'mode de paiement',
            'fournisseur' => 'fournisseur / bénéficiaire',
            'reference' => 'référence',
            'fichier' => 'justificatif',
        ];
    }

    public function messages(): array
    {
        return [
            'fichier.mimes' => 'Le justificatif doit être une photo (JPG, PNG, WEBP) ou un PDF.',
            'fichier.max' => 'Le justificatif ne doit pas dépasser 5 Mo.',
        ];
    }

    public function donnees(): array
    {
        return collect($this->validated())->except(['fichier', 'supprimer_justificatif'])->all();
    }
}
