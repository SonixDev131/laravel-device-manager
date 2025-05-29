<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CommandType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PublishCommandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        // return $this->user()->can('manage', $this->route('room'));
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'command_type' => ['required', Rule::enum(CommandType::class)],
            'target_type' => ['required', 'string', 'in:single,group,all'],
            'computer_id' => ['required_if:target_type,single', 'string', 'exists:computers,id'],
            'computer_ids' => ['required_if:target_type,group', 'array'],
            'computer_ids.*' => ['string', 'exists:computers,id'],
            'payload' => ['sometimes', 'array'],
            'payload.urls' => ['required_if:command_type,BLOCK_WEBSITE', 'array'],
            'payload.urls.*' => ['string', 'regex:/^([a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+)(\/[a-zA-Z0-9-._~:\/?#[\]@!$&\'()*+,;=]*)?$/'],
            'payload.name' => ['required_if:command_type,CUSTOM', 'string'],
            'payload.args' => ['required_if:command_type,CUSTOM', 'string'],

        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payload.urls.required_if' => 'Website URLs are required for the block website command',
            'payload.urls.*.regex' => 'Invalid website format. Enter domain names without http:// or https:// (e.g., example.com)',
        ];
    }
}
