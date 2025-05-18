<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UploadAgentRequest extends FormRequest
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
     * @return array<string, array<int, string|ValidationRule|File>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', File::types('zip')],
            'version' => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
        ];
    }
}
