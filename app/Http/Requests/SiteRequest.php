<?php

namespace App\Http\Requests;

use App\Models\Site;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // seul le gérant est connecté
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge(['code' => mb_strtoupper(trim($this->input('code')))]);
        }
    }

    public function rules(): array
    {
        $siteId = $this->route('site')?->id;

        return [
            'code' => ['nullable', 'string', 'max:20', 'alpha_dash', Rule::unique('sites', 'code')->ignore($siteId)],
            'superviseur_id' => ['nullable', 'integer', 'exists:superviseurs,id,deleted_at,NULL'],
            'nom' => ['required', 'string', 'max:120', Rule::unique('sites', 'nom')->ignore($siteId)],
            'ville' => ['nullable', 'string', 'max:80'],
            'quartier' => ['nullable', 'string', 'max:80'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9 +().-]+$/'],
            'date_ouverture' => ['nullable', 'date', 'before_or_equal:today'],
            'statut' => ['required', Rule::in(array_keys(Site::STATUTS))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'code',
            'superviseur_id' => 'superviseur',
            'nom' => 'nom du site',
            'ville' => 'ville',
            'quartier' => 'quartier',
            'adresse' => 'adresse',
            'telephone' => 'téléphone',
            'date_ouverture' => "date d'ouverture",
            'statut' => 'statut',
            'notes' => 'notes',
        ];
    }
}
