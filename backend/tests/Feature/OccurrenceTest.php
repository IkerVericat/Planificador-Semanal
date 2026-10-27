<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OccurrenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_expands_tasks_in_range_sorted_with_all_day_first(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create(['title' => 'Tarde', 'task_date' => '2026-10-15', 'start_time' => '18:00']);
        Task::factory()->for($user)->allDay()->create(['title' => 'Todo el día', 'task_date' => '2026-10-15']);
        Task::factory()->for($user)->recurring('weekly', [1, 4])->create([
            'title' => 'Curso', 'task_date' => '2026-10-12', 'start_time' => '09:00',
        ]);
        Task::factory()->for($user)->create(['title' => 'Fuera', 'task_date' => '2026-10-20']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/occurrences?from=2026-10-12&to=2026-10-18')->assertOk();

        $this->assertSame(
            [
                ['2026-10-12', 'Curso'],
                ['2026-10-15', 'Todo el día'],
                ['2026-10-15', 'Curso'],
                ['2026-10-15', 'Tarde'],
            ],
            collect($response->json())->map(fn ($o) => [$o['date'], $o['title']])->all(),
        );
        $response->assertJsonPath('0.is_recurring', true)
            ->assertJsonPath('0.start_time', '09:00')
            ->assertJsonStructure([['task_id', 'date', 'start_time', 'end_time', 'title', 'notes', 'category_id', 'is_completed', 'is_recurring']]);
    }

    public function test_validates_range(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/occurrences?from=2026-10-18&to=2026-10-12')
            ->assertUnprocessable()->assertJsonValidationErrors('to');
        $this->getJson('/api/occurrences?from=2026-01-01&to=2027-12-31')
            ->assertUnprocessable()->assertJsonValidationErrors('to');
        $this->getJson('/api/occurrences')
            ->assertUnprocessable()->assertJsonValidationErrors(['from', 'to']);
    }

    public function test_skip_and_complete_single_occurrence(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->recurring('daily')->create(['task_date' => '2026-10-12']);
        Sanctum::actingAs($user);

        $this->putJson("/api/tasks/{$task->id}/occurrences/2026-10-13", ['is_skipped' => true])
            ->assertOk()
            ->assertJsonPath('is_skipped', true);
        $this->putJson("/api/tasks/{$task->id}/occurrences/2026-10-14", ['is_completed' => true])
            ->assertOk();

        $occurrences = collect($this->getJson('/api/occurrences?from=2026-10-12&to=2026-10-14')->json());

        $this->assertSame(['2026-10-12', '2026-10-14'], $occurrences->pluck('date')->all());
        $this->assertSame([false, true], $occurrences->pluck('is_completed')->all());

        // Deshacer: sin cambios la excepción desaparece
        $this->putJson("/api/tasks/{$task->id}/occurrences/2026-10-13", ['is_skipped' => false])->assertOk();
        $this->assertSame(1, $task->exceptions()->count());
    }

    public function test_occurrence_must_exist_and_task_must_be_recurring(): void
    {
        $user = User::factory()->create();
        $weekly = Task::factory()->for($user)->recurring('weekly', [1])->create(['task_date' => '2026-10-12']);
        $single = Task::factory()->for($user)->create(['task_date' => '2026-10-12']);
        Sanctum::actingAs($user);

        $this->putJson("/api/tasks/{$weekly->id}/occurrences/2026-10-13", ['is_completed' => true])->assertNotFound();
        $this->putJson("/api/tasks/{$weekly->id}/occurrences/2026-02-30", ['is_completed' => true])->assertNotFound();
        $this->putJson("/api/tasks/{$single->id}/occurrences/2026-10-12", ['is_completed' => true])->assertUnprocessable();
    }

    public function test_cannot_see_or_modify_another_users_occurrences(): void
    {
        $task = Task::factory()->recurring('daily')->create(['task_date' => '2026-10-12']);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/occurrences?from=2026-10-12&to=2026-10-18')->assertOk()->assertJsonCount(0);
        $this->putJson("/api/tasks/{$task->id}/occurrences/2026-10-13", ['is_skipped' => true])->assertNotFound();

        $this->assertSame(0, $task->exceptions()->count());
    }
}
