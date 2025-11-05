<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'org_id' => ['nullable', 'exists:orgs,id'],
            'serial' => ['required', 'string', 'max:50', 'unique:devices,serial'],
            'alias' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'string', 'in:active,inactive,archived'],
            'location' => ['nullable', 'array'],
            'location.site' => ['sometimes', 'string', 'max:100'],
            'location.floor' => ['sometimes', 'numeric'],
            'location.zone' => ['sometimes', 'string', 'max:100'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
