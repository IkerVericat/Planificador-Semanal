<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOccurrenceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_completed' => ['sometimes', 'boolean'],
            'is_skipped' => ['sometimes', 'boolean'],
            // null o la fecha original devuelven la ocurrencia a su día
            'moved_to_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ];
    }
}
