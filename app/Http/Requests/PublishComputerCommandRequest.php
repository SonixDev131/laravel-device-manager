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
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowedCommands = config('device.commands');

        return [
            'target_type' => ['required', 'in:single,group,all'],
            'computer_id' => ['required_if:target_type,single', 'string', 'exists:computers,id'],
            'computer_ids' => ['required_if:target_type,group', 'array'],
            'computer_ids.*' => ['string', 'exists:computers,id'],
            'command_type' => ['required', 'string', Rule::in($allowedCommands)],
            'params' => 'nullable|array',
        ];
    }
}
