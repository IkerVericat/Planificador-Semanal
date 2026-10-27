<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DateRangeRequest;
use App\Http\Requests\UpdateOccurrenceRequest;
use App\Services\OccurrenceExpander;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class OccurrenceController extends Controller
{
    public function __construct(private OccurrenceExpander $expander) {}

    /**
     * Tareas ya expandidas (recurrencias + excepciones) entre from y to, ambos incluidos.
     */
    public function index(DateRangeRequest $request): JsonResponse
    {
        return response()->json(
            $this->expander->forUser($request->user(), $request->fromDate(), $request->toDate()),
        );
    }

    /**
     * Completa, salta o mueve a otro día una sola ocurrencia de una tarea recurrente.
     * {date} es siempre la fecha original de la ocurrencia (original_date), aunque esté movida.
     */
    public function update(UpdateOccurrenceRequest $request, string $task, string $date): JsonResponse
    {
        $task = $request->user()->tasks()->findOrFail($task);
        Gate::authorize('update', $task);

        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date);

        if ($day === null || $day->toDateString() !== $date) {
            abort(404);
        }

        if (! $task->isRecurring()) {
            throw ValidationException::withMessages([
                'task' => 'La tarea no es recurrente; usa PATCH /api/tasks/{id}.',
            ]);
        }

        if ($this->expander->datesFor($task, $day, $day) === []) {
            abort(404, 'La tarea no tiene ninguna ocurrencia en esa fecha.');
        }

        $exception = $task->exceptions()->firstOrNew(['occurrence_date' => $date]);
        $exception->user_id = $task->user_id;
        $data = $request->validated();

        if (($data['moved_to_date'] ?? null) === $date) {
            $data['moved_to_date'] = null;
        }

        $exception->fill($data);

        if ($exception->is_skipped || $exception->is_completed || $exception->moved_to_date !== null) {
            $exception->save();
        } elseif ($exception->exists) {
            // Sin cambios respecto a la tarea plantilla: no hace falta guardar la excepción
            $exception->delete();
        }

        return response()->json([
            'task_id' => $task->id,
            'date' => $date,
            'is_completed' => (bool) $exception->is_completed,
            'is_skipped' => (bool) $exception->is_skipped,
            'moved_to_date' => $exception->moved_to_date?->toDateString(),
        ]);
    }
}
