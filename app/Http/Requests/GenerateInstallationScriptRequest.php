<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateInstallationScriptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Assuming authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'os_type' => ['required', 'string', 'in:windows,linux,mac'],
            'server_url' => ['required', 'url'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'auto_register' => ['boolean'],
        ];
    }
}
