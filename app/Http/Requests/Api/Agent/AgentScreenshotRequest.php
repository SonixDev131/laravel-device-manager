<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Agent;

use App\Http\Requests\Api\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class AgentScreenshotRequest extends FormRequest
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
            'command_id' => ['required', 'uuid', 'exists:commands,id'],
            'computer_id' => ['required', 'uuid', 'exists:computers,id'],
            'screenshot' => ['required', 'file', 'image', 'max:10240'], // Max 10MB
            'taken_at' => ['required', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'command_id.required' => 'Command ID is required.',
            'command_id.uuid' => 'Command ID must be a valid UUID.',
            'command_id.exists' => 'Command does not exist.',
            'computer_id.required' => 'Computer ID is required.',
            'computer_id.uuid' => 'Computer ID must be a valid UUID.',
            'computer_id.exists' => 'Computer does not exist.',
            'screenshot.required' => 'Screenshot file is required.',
            'screenshot.file' => 'Screenshot must be a valid file.',
            'screenshot.image' => 'Screenshot must be an image file.',
            'screenshot.max' => 'Screenshot file size cannot exceed 10MB.',
            'taken_at.required' => 'Screenshot taken time is required.',
            'taken_at.date' => 'Screenshot taken time must be a valid date.',
        ];
    }
}
