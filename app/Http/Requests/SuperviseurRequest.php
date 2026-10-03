<?php

namespace App\Http\Requests;

class SuperviseurRequest extends PersonneRequest
{
    protected function table(): string
    {
        return 'superviseurs';
    }

    protected function parametreRoute(): string
    {
        return 'superviseur';
    }

    public function rules(): array
    {
        return parent::rules() + [
            'sites' => ['nullable', 'array'],
            'sites.*' => ['integer', 'exists:sites,id,deleted_at,NULL'],
        ];
    }
}
