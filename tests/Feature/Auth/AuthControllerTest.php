<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_as_creator(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Aisyah',
            'email'                 => 'aisyah@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'role'                  => 'creator',
        ]);

        $response->assertStatus(201)->assertJsonStructure(['user', 'token']);
        $this->assertDatabaseHas('users', ['email' => 'aisyah@example.com', 'role' => 'creator']);
    }

    public function test_register_rejects_admin_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Hacker',
            'email'                 => 'hacker@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'role'                  => 'admin',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com', 'role' => 'creator']);

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Other',
            'email'                 => 'taken@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'role'                  => 'brand',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_user_can_login_with_correct_password(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'role' => 'creator']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'a@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'role' => 'creator']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'a@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_suspended_user_cannot_login(): void
    {
        User::factory()->create([
            'email'        => 'a@example.com',
            'role'         => 'creator',
            'suspended_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'a@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403);
    }
}
