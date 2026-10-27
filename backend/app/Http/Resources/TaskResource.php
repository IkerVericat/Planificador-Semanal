<?php

namespace App\Http\Resources;

use App\Models\Task;
use App\Services\OccurrenceExpander;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'notes' => $this->notes,
            'task_date' => $this->task_date->toDateString(),
            'start_time' => OccurrenceExpander::formatTime($this->start_time),
            'end_time' => OccurrenceExpander::formatTime($this->end_time),
            'is_completed' => $this->is_completed,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'recurrence' => $this->recurrence,
            'recurrence_days' => $this->recurrence_days,
            'recurrence_until' => $this->recurrence_until?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
