<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Número de tareas por día de un año (mapa de calor de la vista Año).
 */
class DayStats
{
    public function __construct(private OccurrenceExpander $expander) {}

    /**
     * Solo incluye los días con al menos una tarea, p. ej. ['2026-10-15' => 4].
     *
     * @return array<string, int>
     */
    public function countsForYear(User $user, int $year): array
    {
        $occurrences = $this->expander->forUser(
            $user,
            CarbonImmutable::create($year, 1, 1),
            CarbonImmutable::create($year, 12, 31),
        );

        $counts = [];

        foreach ($occurrences as $occurrence) {
            $counts[$occurrence['date']] = ($counts[$occurrence['date']] ?? 0) + 1;
        }

        return $counts;
    }
}
