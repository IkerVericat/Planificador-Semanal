<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskException;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Expande las tareas "plantilla" de un usuario en ocurrencias concretas dentro de un rango de fechas,
 * aplicando las excepciones (saltada / completada ese día) de task_exceptions.
 *
 * @phpstan-type Occurrence array{
 *     task_id: string, date: string, start_time: string|null, end_time: string|null,
 *     title: string, notes: string|null, category_id: string|null,
 *     is_completed: bool, is_recurring: bool
 * }
 */
class OccurrenceExpander
{
    /** Rango máximo que acepta GET /api/occurrences (la vista Año usa /api/stats/days). */
    public const MAX_RANGE_DAYS = 400;

    /**
     * Ocurrencias ordenadas por fecha y hora (las de todo el día primero).
     *
     * @return list<Occurrence>
     */
    public function forUser(User $user, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $tasks = $user->tasks()
            ->where(function (Builder $query) use ($from, $to) {
                $query
                    ->where(fn (Builder $q) => $q
                        ->where('recurrence', 'none')
                        ->whereBetween('task_date', [$from->toDateString(), $to->toDateString()]))
                    ->orWhere(fn (Builder $q) => $q
                        ->where('recurrence', '<>', 'none')
                        ->where('task_date', '<=', $to->toDateString())
                        ->where(fn (Builder $q) => $q
                            ->whereNull('recurrence_until')
                            ->orWhere('recurrence_until', '>=', $from->toDateString())));
            })
            ->get();

        $exceptions = $user->taskExceptions()
            ->whereBetween('occurrence_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn (TaskException $e) => $e->task_id.'|'.$e->occurrence_date->toDateString());

        $occurrences = [];

        foreach ($tasks as $task) {
            foreach ($this->datesFor($task, $from, $to) as $date) {
                /** @var TaskException|null $exception */
                $exception = $exceptions->get($task->id.'|'.$date);

                if ($exception?->is_skipped) {
                    continue;
                }

                $occurrences[] = [
                    'task_id' => $task->id,
                    'date' => $date,
                    'start_time' => self::formatTime($task->start_time),
                    'end_time' => self::formatTime($task->end_time),
                    'title' => $task->title,
                    'notes' => $task->notes,
                    'category_id' => $task->category_id,
                    'is_completed' => $task->isRecurring()
                        ? (bool) $exception?->is_completed
                        : $task->is_completed,
                    'is_recurring' => $task->isRecurring(),
                ];
            }
        }

        usort($occurrences, fn (array $a, array $b) => [$a['date'], $a['start_time'] !== null, $a['start_time'], $a['title']]
            <=> [$b['date'], $b['start_time'] !== null, $b['start_time'], $b['title']]);

        return $occurrences;
    }

    /**
     * Fechas (Y-m-d) en las que cae la tarea dentro del rango, sin aplicar excepciones.
     *
     * @return list<string>
     */
    public function datesFor(Task $task, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $taskDate = $task->task_date->startOfDay();
        $start = $taskDate->max($from->startOfDay());
        $end = $to->startOfDay();

        if ($task->recurrence_until !== null) {
            $end = $end->min($task->recurrence_until->startOfDay());
        }

        if ($start->gt($end)) {
            return [];
        }

        $dates = match ($task->recurrence) {
            'daily' => $this->daysBetween($start, $end),
            'weekly' => $this->weekly($task, $start, $end),
            'monthly' => $this->monthly($taskDate, $start, $end),
            'yearly' => $this->yearly($taskDate, $start, $end),
            default => $taskDate->betweenIncluded($start, $end) ? [$taskDate] : [],
        };

        return array_map(fn (CarbonImmutable $d) => $d->toDateString(), $dates);
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function daysBetween(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $days = [];

        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            $days[] = $day;
        }

        return $days;
    }

    /**
     * Días de la semana de recurrence_days (1 = lunes … 7 = domingo); sin días, el de task_date.
     *
     * @return list<CarbonImmutable>
     */
    private function weekly(Task $task, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $weekdays = $task->recurrence_days ?: [$task->task_date->dayOfWeekIso];

        return array_values(array_filter(
            $this->daysBetween($start, $end),
            fn (CarbonImmutable $day) => in_array($day->dayOfWeekIso, $weekdays, true),
        ));
    }

    /**
     * Mismo día del mes que task_date; los meses en los que no existe (31 de abril) se omiten.
     *
     * @return list<CarbonImmutable>
     */
    private function monthly(CarbonImmutable $taskDate, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $dates = [];

        for ($month = $start->startOfMonth(); $month->lte($end); $month = $month->addMonthNoOverflow()) {
            if ($taskDate->day > $month->daysInMonth) {
                continue;
            }

            $date = $month->setDay($taskDate->day);

            if ($date->betweenIncluded($start, $end)) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    /**
     * Mismo día y mes que task_date; el 29 de febrero solo en años bisiestos.
     *
     * @return list<CarbonImmutable>
     */
    private function yearly(CarbonImmutable $taskDate, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $dates = [];

        for ($year = $start->year; $year <= $end->year; $year++) {
            if (! checkdate($taskDate->month, $taskDate->day, $year)) {
                continue;
            }

            $date = CarbonImmutable::create($year, $taskDate->month, $taskDate->day);

            if ($date->betweenIncluded($start, $end)) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    /**
     * La columna time devuelve "09:30:00"; la API usa "09:30".
     */
    public static function formatTime(?string $time): ?string
    {
        return $time === null ? null : substr($time, 0, 5);
    }
}
