<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cambio sobre una única ocurrencia de una tarea recurrente: saltarla, completarla
 * o moverla a otro día (moved_to_date) sin tocar el resto de la serie.
 */
#[Fillable(['occurrence_date', 'moved_to_date', 'is_skipped', 'is_completed'])]
class TaskException extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurrence_date' => 'immutable_date',
            'moved_to_date' => 'immutable_date',
            'is_skipped' => 'boolean',
            'is_completed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
