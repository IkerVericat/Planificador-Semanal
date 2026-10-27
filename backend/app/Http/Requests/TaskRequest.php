<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Services\OccurrenceExpander;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Crear (POST) y editar (PATCH) tareas.
 *
 * En PATCH se rellenan los campos que no llegan con los valores actuales de la tarea,
 * así las reglas que cruzan campos (hora fin > hora inicio, fin de recurrencia >= fecha)
 * se comprueban igual que al crear y nunca llega a saltar un CHECK de la base de datos.
 */
class TaskRequest extends FormRequest
{
    private ?Task $resolvedTask = null;

    /**
     * Tarea de la ruta, buscada solo entre las del usuario (404 si es de otro).
     */
    public function task(): ?Task
    {
        $id = $this->route('task');

        if ($id === null) {
            return null;
        }

        return $this->resolvedTask ??= $this->user()->tasks()->findOrFail($id);
    }

    protected function prepareForValidation(): void
    {
        $task = $this->task();

        if ($task === null) {
            return;
        }

        $current = [
            'category_id' => $task->category_id,
            'title' => $task->title,
            'notes' => $task->notes,
            'task_date' => $task->task_date->toDateString(),
            'start_time' => OccurrenceExpander::formatTime($task->start_time),
            'end_time' => OccurrenceExpander::formatTime($task->end_time),
            'recurrence' => $task->recurrence,
            'recurrence_days' => $task->recurrence_days,
            'recurrence_until' => $task->recurrence_until?->toDateString(),
        ];

        $this->merge(array_diff_key($current, $this->all()));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'task_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_with:end_time'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'category_id' => ['nullable', 'uuid',
                Rule::exists('categories', 'id')->where('user_id', $this->user()->id),
            ],
            'is_completed' => ['sometimes', 'boolean'],
            'recurrence' => ['sometimes', Rule::in(Task::RECURRENCES)],
            'recurrence_days' => ['nullable', 'array'],
            'recurrence_days.*' => ['integer', 'between:1,7', 'distinct'],
            'recurrence_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:task_date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        // Los días de la semana solo tienen sentido en 'weekly' y el fin solo si se repite
        $recurrence = $data['recurrence'] ?? 'none';

        if ($recurrence !== 'weekly') {
            $data['recurrence_days'] = null;
        } elseif (! empty($data['recurrence_days'])) {
            $data['recurrence_days'] = collect($data['recurrence_days'])->map(fn ($d) => (int) $d)->sort()->values()->all();
        }

        if ($recurrence === 'none') {
            $data['recurrence_until'] = null;
        }

        return $data;
    }
}
