<?php

namespace App\Http\Requests;

class AgentRequest extends PersonneRequest
{
    protected function table(): string
    {
        return 'agents';
    }

    protected function parametreRoute(): string
    {
        return 'agent';
    }

    public function rules(): array
    {
        return parent::rules() + [
            'site_id' => ['nullable', 'integer', 'exists:sites,id,deleted_at,NULL'],
        ];
    }
}
