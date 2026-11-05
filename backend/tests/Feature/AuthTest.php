<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_token_and_default_categories(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ana',
            'email' => 'Ana@Example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.name', 'Ana')
            ->assertJsonPath('user.email', 'ana@example.com')
            ->assertJsonPath('user.timezone', 'Europe/Madrid')
            ->assertJsonPath('user.default_view', 'week')
            ->assertJsonStructure(['user' => ['id'], 'token']);

        $user = User::firstWhere('email', 'ana@example.com');
        $this->assertSame(
            ['Fijo', 'Urgente', 'Importante', 'Nueva'],
            $user->categories()->orderBy('position')->pluck('name')->all(),
        );

        $this->withToken($response->json('token'))->getJson('/api/me')->assertOk();
    }

    public function test_register_validates_input(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'ANA@example.com',
            'password' => 'corta',
            'password_confirmation' => 'otra',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_login_returns_token_and_email_is_case_insensitive(): void
    {
        User::factory()->create(['email' => 'ana@example.com', 'password' => 'secreto123']);

        $this->postJson('/api/auth/login', ['email' => 'ANA@example.com', 'password' => 'secreto123'])
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'email'], 'token']);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        User::factory()->create(['email' => 'ana@example.com', 'password' => 'secreto123']);

        $this->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'incorrecta'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_login_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'x']);
        }

        $this->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'x'])
            ->assertTooManyRequests();
    }

    public function test_login_throttle_uses_client_ip_behind_the_proxy(): void
    {
        $fromProxy = fn (string $clientIp) => $this
            ->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])
            ->withHeader('X-Forwarded-For', $clientIp);

        for ($i = 0; $i < 5; $i++) {
            $fromProxy('203.0.113.10')->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'x']);
        }

        $fromProxy('203.0.113.10')->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'x'])
            ->assertTooManyRequests();
        $fromProxy('198.51.100.20')->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'x'])
            ->assertUnprocessable();
    }

    public function test_forwarded_headers_from_public_ips_are_ignored(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
                ->withHeader('X-Forwarded-For', "198.51.100.{$i}")
                ->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'x']);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
            ->withHeader('X-Forwarded-For', '198.51.100.50')
            ->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'x'])
            ->assertTooManyRequests();
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('web')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_protected_routes_require_token(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        $this->get('/api/me')->assertUnauthorized();
    }

    public function test_forgot_password_sends_link_and_hides_unknown_emails(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'ana@example.com'])->assertOk();
        $this->postJson('/api/auth/forgot-password', ['email' => 'nadie@example.com'])->assertOk();

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, config('app.frontend_url').'/reset-password?token=');
        });
        Notification::assertCount(1);
    }

    public function test_reset_password_changes_password_and_revokes_tokens(): void
    {
        $user = User::factory()->create(['email' => 'ana@example.com']);
        $user->createToken('móvil');
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'ana@example.com',
            'token' => $token,
            'password' => 'nueva-clave',
            'password_confirmation' => 'nueva-clave',
        ])->assertOk();

        $this->assertSame(0, $user->tokens()->count());
        $this->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'nueva-clave'])->assertOk();
    }

    public function test_reset_password_with_invalid_token_fails(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'ana@example.com',
            'token' => 'no-vale',
            'password' => 'nueva-clave',
            'password_confirmation' => 'nueva-clave',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }
}
