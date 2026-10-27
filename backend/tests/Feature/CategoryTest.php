<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_own_categories_ordered_by_position(): void
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->create(['name' => 'Segunda', 'position' => 2]);
        Category::factory()->for($user)->create(['name' => 'Primera', 'position' => 1]);
        Category::factory()->create(['name' => 'De otro']);
        Sanctum::actingAs($user);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.name', 'Primera')
            ->assertJsonPath('1.name', 'Segunda');
    }

    public function test_creates_category(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/categories', ['name' => 'Gimnasio', 'color' => '#123ABC'])
            ->assertCreated()
            ->assertJsonPath('name', 'Gimnasio')
            ->assertJsonPath('position', 0);
    }

    public function test_validates_color_and_unique_name_per_user(): void
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->create(['name' => 'Fijo']);
        Category::factory()->create(['name' => 'Otra']);
        Sanctum::actingAs($user);

        $this->postJson('/api/categories', ['name' => 'Fijo', 'color' => 'rojo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'color']);

        // El nombre solo es único dentro de las categorías del usuario
        $this->postJson('/api/categories', ['name' => 'Otra', 'color' => '#000000'])->assertCreated();
    }

    public function test_updates_and_deletes_category_keeping_its_tasks(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $task = Task::factory()->for($user)->create(['category_id' => $category->id]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/categories/{$category->id}", ['color' => '#FFFFFF'])
            ->assertOk()
            ->assertJsonPath('color', '#FFFFFF');

        $this->deleteJson("/api/categories/{$category->id}")->assertNoContent();

        $this->assertModelMissing($category);
        $this->assertNull($task->fresh()->category_id);
    }

    public function test_cannot_modify_another_users_category(): void
    {
        $category = Category::factory()->create(['name' => 'Ajena', 'color' => '#111111']);
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/categories/{$category->id}", ['name' => 'Mía'])->assertNotFound();
        $this->patchJson("/api/categories/{$category->id}", ['color' => 'no-válido'])->assertNotFound();
        $this->deleteJson("/api/categories/{$category->id}")->assertNotFound();

        $this->assertSame('Ajena', $category->fresh()->name);
    }

    public function test_invalid_id_returns_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson('/api/categories/no-es-un-uuid')->assertNotFound();
    }
}
