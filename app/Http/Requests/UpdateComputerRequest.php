<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateComputerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can(PermissionsEnum::MANAGE_COMPUTERS->value);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $computerId = $this->route('computer');

        return [
            'hostname' => 'required|string|max:255',
            'ip_address' => 'nullable|ip',
            'mac_address' => [
                'required',
                'string',
                'max:17',
                Rule::unique('computers', 'mac_address')->ignore($computerId),
            ],
            'pos_row' => 'required|integer',
            'pos_col' => 'required|integer',
            'is_online' => 'sometimes|boolean',
        ];
    }
}
