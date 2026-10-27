<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_task(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/tasks', [
            'title' => 'Curso',
            'task_date' => '2026-10-12',
            'start_time' => '09:00',
            'end_time' => '10:30',
            'category_id' => $category->id,
            'recurrence' => 'weekly',
            'recurrence_days' => [4, 1],
        ])
            ->assertCreated()
            ->assertJsonPath('title', 'Curso')
            ->assertJsonPath('start_time', '09:00')
            ->assertJsonPath('end_time', '10:30')
            ->assertJsonPath('recurrence_days', [1, 4])
            ->assertJsonPath('is_completed', false);

        $this->assertSame([1, 4], Task::first()->recurrence_days);
    }

    public function test_all_day_task_without_times(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/tasks', ['title' => 'Cumpleaños', 'task_date' => '2026-10-20'])
            ->assertCreated()
            ->assertJsonPath('start_time', null)
            ->assertJsonPath('recurrence', 'none')
            ->assertJsonPath('recurrence_days', null);
    }

    public function test_validates_task(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/tasks', [
            'title' => '   ',
            'task_date' => '15/10/2026',
            'end_time' => '08:00',
            'recurrence' => 'hourly',
            'recurrence_days' => [8],
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'title', 'task_date', 'start_time', 'recurrence', 'recurrence_days.0',
        ]);

        $this->postJson('/api/tasks', [
            'title' => 'Tarea',
            'task_date' => '2026-10-15',
            'start_time' => '10:00',
            'end_time' => '09:00',
            'recurrence' => 'daily',
            'recurrence_until' => '2026-10-01',
        ])->assertUnprocessable()->assertJsonValidationErrors(['end_time', 'recurrence_until']);
    }

    public function test_cannot_use_another_users_category(): void
    {
        $foreign = Category::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/tasks', [
            'title' => 'Tarea',
            'task_date' => '2026-10-15',
            'category_id' => $foreign->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function test_patch_validates_against_current_values(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(['start_time' => '09:00', 'end_time' => '10:00']);
        Sanctum::actingAs($user);

        $this->patchJson("/api/tasks/{$task->id}", ['end_time' => '08:00'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('end_time');

        $this->patchJson("/api/tasks/{$task->id}", ['title' => 'Nuevo título'])
            ->assertOk()
            ->assertJsonPath('title', 'Nuevo título')
            ->assertJsonPath('end_time', '10:00');
    }

    public function test_completing_task_sets_completed_at(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->patchJson("/api/tasks/{$task->id}", ['is_completed' => true])
            ->assertOk()
            ->assertJsonPath('is_completed', true);
        $this->assertNotNull($task->fresh()->completed_at);

        $this->patchJson("/api/tasks/{$task->id}", ['is_completed' => false]);
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_changing_recurrence_clears_weekly_fields(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->recurring('weekly', [1, 3])->create(['recurrence_until' => '2026-12-31']);
        Sanctum::actingAs($user);

        $this->patchJson("/api/tasks/{$task->id}", ['recurrence' => 'none'])
            ->assertOk()
            ->assertJsonPath('recurrence_days', null)
            ->assertJsonPath('recurrence_until', null);
    }

    public function test_shows_and_deletes_own_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/tasks/{$task->id}")->assertOk()->assertJsonPath('id', $task->id);
        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();
        $this->assertModelMissing($task);
    }

    public function test_cannot_read_or_modify_another_users_task(): void
    {
        $task = Task::factory()->create(['title' => 'Ajena']);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/tasks/{$task->id}")->assertNotFound();
        $this->patchJson("/api/tasks/{$task->id}", ['title' => 'Mía'])->assertNotFound();
        $this->patchJson("/api/tasks/{$task->id}", ['end_time' => 'mal'])->assertNotFound();
        $this->deleteJson("/api/tasks/{$task->id}")->assertNotFound();

        $this->assertSame('Ajena', $task->fresh()->title);
    }

    public function test_client_cannot_set_user_id(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/tasks', ['title' => 'Tarea', 'task_date' => '2026-10-15', 'user_id' => $other->id])
            ->assertCreated();

        $this->assertSame($user->id, Task::first()->user_id);
    }
}
