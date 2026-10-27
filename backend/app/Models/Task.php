<?php

namespace App\Models;

use App\Casts\PgSmallintArray;
use App\Policies\TaskPolicy;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tarea "plantilla". Si es recurrente, sus ocurrencias se calculan en OccurrenceExpander.
 */
#[Fillable([
    'category_id', 'title', 'notes', 'task_date', 'start_time', 'end_time',
    'is_completed', 'recurrence', 'recurrence_days', 'recurrence_until',
])]
#[UsePolicy(TaskPolicy::class)]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, HasUuids;

    public const RECURRENCES = ['none', 'daily', 'weekly', 'monthly', 'yearly'];

    /**
     * Mismos valores por defecto que la base de datos.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_completed' => false,
        'recurrence' => 'none',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'task_date' => 'immutable_date',
            'recurrence_until' => 'immutable_date',
            'recurrence_days' => PgSmallintArray::class,
            'is_completed' => 'boolean',
            'completed_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            if ($task->isDirty('is_completed')) {
                $task->completed_at = $task->is_completed ? now() : null;
            }
        });
    }

    public function isRecurring(): bool
    {
        return $this->recurrence !== 'none';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<TaskException, $this>
     */
    public function exceptions(): HasMany
    {
        return $this->hasMany(TaskException::class);
    }
}
