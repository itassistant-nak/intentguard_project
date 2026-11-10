<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@intentguard.internal',
            'password' => Hash::make('IntentGuardAdminPass!2026'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@intentguard.internal',
            'password' => 'IntentGuardAdminPass!2026',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'admin@intentguard.internal')
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'email', 'role'],
                ],
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@intentguard.internal',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@intentguard.internal',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_unauthenticated_request_returns_401_json_instead_of_missing_route_exception(): void
    {
        // Direct request without authentication header
        $response = $this->get('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_authenticated_user_can_access_profile_and_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Profile access
        $profileResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $profileResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', $user->email);

        // Logout
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // Token revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_can_register_as_customer(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Alice Customer',
            'email' => 'alice@customer.example',
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'alice@customer.example')
            ->assertJsonPath('data.user.role', 'customer')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'email', 'role'],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alice@customer.example',
            'role' => 'customer',
        ]);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Bob Duplicate',
            'email' => 'existing@example.com',
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Short Pass',
            'email' => 'short@example.com',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}

