<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComputerRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'ip_address' => 'nullable|ip',
            'room_id' => 'required|exists:rooms,id',
            'mac_address' => 'required|string|max:17',
            'pos_row' => 'required|integer',
            'pos_col' => 'required|integer',
            'is_online' => 'sometimes|boolean',
        ];
    }
}
