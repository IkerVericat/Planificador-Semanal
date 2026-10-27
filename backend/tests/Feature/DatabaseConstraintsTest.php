<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La base de datos rechaza datos inválidos aunque la validación de la app fallara.
 */
class DatabaseConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_cannot_point_to_another_users_category(): void
    {
        $foreign = Category::factory()->create();

        $this->expectException(QueryException::class);
        Task::factory()->create(['category_id' => $foreign->id]);
    }

    public function test_rejects_invalid_category_color(): void
    {
        $this->expectException(QueryException::class);
        Category::factory()->create(['color' => 'rojo']);
    }

    public function test_rejects_end_time_before_start_time(): void
    {
        $this->expectException(QueryException::class);
        Task::factory()->create(['start_time' => '10:00', 'end_time' => '09:00']);
    }

    public function test_rejects_invalid_weekday(): void
    {
        $this->expectException(QueryException::class);
        Task::factory()->recurring('weekly', [8])->create();
    }

    public function test_email_is_unique_ignoring_case(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->expectException(QueryException::class);
        User::factory()->create(['email' => 'ANA@example.com']);
    }

    public function test_deleting_user_cascades_to_their_data(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $task = Task::factory()->for($user)->create(['category_id' => $category->id]);

        $user->delete();

        $this->assertModelMissing($category);
        $this->assertModelMissing($task);
    }
}
