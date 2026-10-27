<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_own_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('week_starts_on', 1)
            ->assertJsonMissingPath('password');
    }

    public function test_updates_preferences(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/me', ['timezone' => 'America/Mexico_City', 'default_view' => 'month'])
            ->assertOk()
            ->assertJsonPath('timezone', 'America/Mexico_City')
            ->assertJsonPath('default_view', 'month');
    }

    public function test_rejects_invalid_preferences(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/me', ['timezone' => 'Marte/Olympus', 'week_starts_on' => 9, 'default_view' => 'day'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['timezone', 'week_starts_on', 'default_view']);
    }

    public function test_cannot_change_own_id_or_another_users_data(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['name' => 'Otra']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', ['id' => $other->id, 'name' => 'Cambiado'])->assertOk();

        $this->assertSame('Cambiado', $user->fresh()->name);
        $this->assertSame('Otra', $other->fresh()->name);
    }
}
