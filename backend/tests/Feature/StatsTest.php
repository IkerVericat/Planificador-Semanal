<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_tasks_per_day_of_the_year(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->count(2)->create(['task_date' => '2026-10-15']);
        Task::factory()->for($user)->recurring('monthly')->create(['task_date' => '2026-11-15']);
        Task::factory()->create(['task_date' => '2026-10-15']); // de otro usuario
        Sanctum::actingAs($user);

        $this->getJson('/api/stats/days?year=2026')
            ->assertOk()
            ->assertExactJson(['2026-10-15' => 2, '2026-11-15' => 1, '2026-12-15' => 1]);
    }

    public function test_empty_year_returns_an_object(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/stats/days?year=2030')->assertOk();

        $this->assertSame('{}', $response->getContent());
    }

    public function test_requires_valid_year(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/stats/days?year=dos-mil')->assertUnprocessable()->assertJsonValidationErrors('year');
    }
}
