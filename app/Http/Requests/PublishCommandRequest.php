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
            'params' => ['sometimes', 'array'],
        ];
    }
}
