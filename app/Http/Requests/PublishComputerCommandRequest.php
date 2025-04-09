<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PublishComputerCommandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Actual authorization will be handled by a policy or middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'target_type' => ['required', 'string', Rule::in(['single', 'group', 'all'])],
            'command_type' => ['required', 'string'],
            'computer_id' => ['required_if:target_type,single', 'string', 'exists:computers,uuid'],
            'computer_ids' => ['required_if:target_type,group', 'array', 'min:1'],
            'computer_ids.*' => ['string', 'exists:computers,uuid'],
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
            'target_type.required' => 'Please specify a target type.',
            'target_type.in' => 'The target type must be single, group, or all.',
            'command_type.required' => 'Please specify a command type.',
            'computer_id.required_if' => 'Please select a computer when targeting a single device.',
            'computer_id.exists' => 'The selected computer does not exist.',
            'computer_ids.required_if' => 'Please select at least one computer when targeting a group.',
            'computer_ids.min' => 'Please select at least one computer when targeting a group.',
            'computer_ids.*.exists' => 'One or more selected computers do not exist.',
        ];
    }
}
