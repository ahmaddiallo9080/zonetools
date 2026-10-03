<?php

namespace App\Http\Requests;

use App\Models\Paiement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Le formulaire envoie « superviseur:3 » ou « agent:12 »
        [$type, $id] = array_pad(explode(':', (string) $this->input('beneficiaire')), 2, null);

        $this->merge([
            'code' => $this->filled('code') ? mb_strtoupper(trim($this->input('code'))) : null,
            'beneficiaire_type' => $type,
            'beneficiaire_id' => $id,
            'montant' => is_string($this->input('montant')) ? preg_replace('/[^\d]/', '', $this->input('montant')) : $this->input('montant'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('paiement')?->id;
        $table = $this->input('beneficiaire_type') === 'agent' ? 'agents' : 'superviseurs';

        return [
            'code' => ['nullable', 'string', 'max:20', 'alpha_dash', "unique:paiements,code,{$id}"],
            'beneficiaire' => ['required'],
            'beneficiaire_type' => ['required', Rule::in(array_keys(Paiement::TYPES))],
            'beneficiaire_id' => ['required', 'integer', "exists:{$table},id"],
            'date_paiement' => ['required', 'date', 'before_or_equal:today'],
            'montant' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'mode' => ['required', Rule::in(array_keys(Paiement::MODES))],
            'reference' => ['nullable', 'string', 'max:80'],
            'periode_du' => ['nullable', 'date'],
            'periode_au' => ['nullable', 'date', 'after_or_equal:periode_du'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'beneficiaire' => 'bénéficiaire',
            'beneficiaire_type' => 'bénéficiaire',
            'beneficiaire_id' => 'bénéficiaire',
            'date_paiement' => 'date du paiement',
            'mode' => 'mode de paiement',
            'reference' => 'référence',
            'periode_du' => 'début de période',
            'periode_au' => 'fin de période',
        ];
    }

    /** Données à enregistrer (sans le champ combiné « beneficiaire »). */
    public function donnees(): array
    {
        return collect($this->validated())->except('beneficiaire')->all();
    }
}
