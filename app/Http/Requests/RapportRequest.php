<?php

namespace App\Http\Requests;

use App\Models\Lot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RapportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Le lot concerné (création : paramètre {lot} ; modification : lot du rapport). */
    public function lot(): Lot
    {
        return $this->route('lot') ?? $this->route('rapport')->lot;
    }

    protected function prepareForValidation(): void
    {
        $entier = fn ($v) => is_string($v) ? preg_replace('/[^\d]/', '', $v) : $v;

        $lignes = collect($this->input('lignes', []))
            ->map(fn ($l) => [
                'vendus' => $entier($l['vendus'] ?? null) === '' ? null : $entier($l['vendus'] ?? null),
                'defectueux' => filled($l['defectueux'] ?? null) ? $entier($l['defectueux']) : 0,
            ])
            ->all();

        $this->merge([
            'code' => $this->filled('code') ? mb_strtoupper(trim($this->input('code'))) : null,
            'montant_verse' => $entier($this->input('montant_verse')),
            'lignes' => $lignes,
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('rapport')?->id;

        return [
            'code' => ['nullable', 'string', 'max:20', 'alpha_dash', "unique:rapports,code,{$id}"],
            'date_rapport' => ['required', 'date', 'after_or_equal:' . $this->lot()->date_remise->format('Y-m-d'), 'before_or_equal:today'],
            'montant_verse' => ['required', 'integer', 'min:0'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'lignes' => ['required', 'array'],
            'lignes.*.vendus' => ['required', 'integer', 'min:0'],
            'lignes.*.defectueux' => ['required', 'integer', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $lignesLot = $this->lot()->lignes()->with('forfait')->get()->keyBy('id');
                $saisies = $this->input('lignes', []);

                foreach ($lignesLot as $id => $ligne) {
                    if (! isset($saisies[$id])) {
                        $validator->errors()->add("lignes.{$id}.vendus", "Le forfait {$ligne->forfait->nom} n'a pas été renseigné.");
                        continue;
                    }

                    $total = (int) $saisies[$id]['vendus'] + (int) $saisies[$id]['defectueux'];
                    if ($total > $ligne->quantite) {
                        $validator->errors()->add(
                            "lignes.{$id}.vendus",
                            "{$ligne->forfait->nom} : vendus + défectueux ({$total}) dépasse la quantité remise ({$ligne->quantite})."
                        );
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'date_rapport' => 'date du rapport',
            'montant_verse' => 'montant versé',
            'lignes.*.vendus' => 'tickets vendus',
            'lignes.*.defectueux' => 'tickets défectueux',
        ];
    }

    public function messages(): array
    {
        return [
            'date_rapport.after_or_equal' => 'La date du rapport ne peut pas être avant la remise du lot.',
        ];
    }
}
