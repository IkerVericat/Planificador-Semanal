<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\User;

/**
 * Crea las categorías iniciales (Fijo, Urgente, Importante, Nueva) de un usuario recién registrado.
 * Sustituye al trigger create_default_categories de schema.sql.
 */
class CreateDefaultCategories
{
    public function __invoke(User $user): void
    {
        $user->categories()->createMany(Category::DEFAULTS);
    }
}
