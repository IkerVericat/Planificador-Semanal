<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Convierte entre el literal de PostgreSQL `{1,3,5}` (columna smallint[]) y un array PHP [1, 3, 5].
 *
 * @implements CastsAttributes<list<int>|null, list<int>|null>
 */
class PgSmallintArray implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return list<int>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        $inner = trim((string) $value, '{}');

        if ($inner === '') {
            return [];
        }

        return array_map('intval', explode(',', $inner));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $numbers = array_map('intval', array_values((array) $value));

        return '{'.implode(',', $numbers).'}';
    }
}
