<?php

namespace App\Http\Requests;

use App\Models\Agent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $taux = fn ($v) => filled($v) ? str_replace(',', '.', (string) $v) : 0;
        $entier = fn ($v) => is_string($v) ? preg_replace('/[^\d]/', '', $v) : $v;

        // On ignore les lignes complètement vides
        $lignes = collect($this->input('lignes', []))
            ->filter(fn ($l) => filled($l['forfait_id'] ?? null) || filled($l['quantite'] ?? null))
            ->map(fn ($l) => ['forfait_id' => $l['forfait_id'] ?? null, 'quantite' => $entier($l['quantite'] ?? null)])
            ->values()
            ->all();

        $this->merge([
            'code' => $this->filled('code') ? mb_strtoupper(trim($this->input('code'))) : null,
            'taux_agent' => $taux($this->input('taux_agent')),
            'taux_superviseur' => $taux($this->input('taux_superviseur')),
            'lignes' => $lignes,
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('lot')?->id;

        return [
            'code' => ['nullable', 'string', 'max:20', 'alpha_dash', "unique:lots,code,{$id}"],
            'site_id' => ['required', 'integer', 'exists:sites,id,deleted_at,NULL'],
            'agent_id' => ['required', 'integer', 'exists:agents,id,deleted_at,NULL'],
            'date_remise' => ['required', 'date'],
            'date_fin_prevue' => ['nullable', 'date', 'after_or_equal:date_remise'],
            'taux_agent' => ['required', 'numeric', 'min:0', 'max:100'],
            'taux_superviseur' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.forfait_id' => ['required', 'integer', 'distinct', 'exists:forfaits,id,deleted_at,NULL'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['site_id', 'agent_id'])) {
                    return;
                }

                // L'agent doit travailler sur le site choisi
                $agent = Agent::find($this->input('agent_id'));
                if ($agent && (int) $agent->site_id !== (int) $this->input('site_id')) {
                    $validator->errors()->add('agent_id', "Cet agent n'est pas affecté au site choisi.");
                }

                // Le total des commissions ne peut pas dépasser 100 %
                if ((float) $this->input('taux_agent') + (float) $this->input('taux_superviseur') > 100) {
                    $validator->errors()->add('taux_superviseur', 'Le total des commissions ne peut pas dépasser 100 %.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'site_id' => 'site',
            'agent_id' => 'agent',
            'date_remise' => 'date de remise',
            'date_fin_prevue' => 'date de fin prévue',
            'taux_agent' => 'commission agent',
            'taux_superviseur' => 'commission superviseur',
            'lignes' => 'tickets du lot',
            'lignes.*.forfait_id' => 'forfait',
            'lignes.*.quantite' => 'quantité',
        ];
    }

    public function messages(): array
    {
        return [
            'lignes.required' => 'Ajoutez au moins un forfait au lot.',
            'lignes.min' => 'Ajoutez au moins un forfait au lot.',
            'lignes.*.forfait_id.distinct' => 'Un même forfait apparaît plusieurs fois : regroupez les quantités sur une seule ligne.',
            'date_fin_prevue.after_or_equal' => 'La date de fin prévue doit être après la date de remise.',
        ];
    }
}
