<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Crear (POST) y editar (PATCH) categorías. En PATCH los campos son opcionales.
 */
class CategoryRequest extends FormRequest
{
    private ?Category $resolvedCategory = null;

    /**
     * Categoría de la ruta, buscada solo entre las del usuario (404 si es de otro).
     */
    public function category(): ?Category
    {
        $id = $this->route('category');

        if ($id === null) {
            return null;
        }

        return $this->resolvedCategory ??= $this->user()->categories()->findOrFail($id);
    }

    protected function prepareForValidation(): void
    {
        $this->category();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'required', 'string', 'max:50',
                Rule::unique('categories', 'name')
                    ->where('user_id', $this->user()->id)
                    ->ignore($this->category()?->id),
            ],
            'color' => [$required, 'required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'position' => ['sometimes', 'required', 'integer', 'between:0,32767'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'color.regex' => 'El color debe tener el formato #RRGGBB.',
        ];
    }
}
