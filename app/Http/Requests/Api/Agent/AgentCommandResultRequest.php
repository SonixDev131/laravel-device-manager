<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Agent;

use App\Http\Requests\Api\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class AgentCommandResultRequest extends FormRequest
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
            'command_id' => ['required', 'uuid'],
            'completed_at' => ['required', 'date'],
            'error' => ['nullable', 'string'],
            'output' => ['nullable', 'string'],
        ];
    }
}
