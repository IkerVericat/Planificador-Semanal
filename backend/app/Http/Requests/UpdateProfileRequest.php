<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'timezone' => ['sometimes', 'required', 'string', 'timezone:all'],
            'week_starts_on' => ['sometimes', 'required', 'integer', 'between:0,6'],
            'default_view' => ['sometimes', 'required', Rule::in(['week', 'month', 'year'])],
        ];
    }
}
