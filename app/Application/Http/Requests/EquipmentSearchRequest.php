<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EquipmentSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
