<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Agent;

use App\Http\Requests\Api\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class RegisterAgentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mac_address' => ['required', 'string', 'regex:/^([0-9A-Fa-f]{2}-){5}([0-9A-Fa-f]{2})$/'],
            'hostname' => ['required', 'string', 'max:255'],
        ];
    }
}
