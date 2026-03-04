<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_user_data_for_valid_credentials(): void
    {
        $user = User::create([
            'ime' => 'Ana',
            'prezime' => 'Jovanovic',
            'email' => 'ana@student.rs',
            'password' => Hash::make('password'),
            'uloga' => 'STUDENT',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ana@student.rs',
            'password' => 'password',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user' => ['id', 'ime', 'prezime', 'email', 'uloga'],
            ])
            ->assertJson([
                'message' => 'Uspešna prijava.',
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'ime' => 'Ana',
                    'prezime' => 'Jovanovic',
                    'email' => 'ana@student.rs',
                    'uloga' => 'STUDENT',
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_returns_validation_error_when_payload_is_missing_required_fields(): void
    {
        $response = $this->postJson('/api/login', []);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validacija nije prošla.')
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_returns_unauthorized_for_invalid_password(): void
    {
        User::create([
            'ime' => 'Ana',
            'prezime' => 'Jovanovic',
            'email' => 'ana@student.rs',
            'password' => Hash::make('password'),
            'uloga' => 'STUDENT',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ana@student.rs',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Pogrešan email ili lozinka.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::create([
            'ime' => 'Mika',
            'prezime' => 'Mikic',
            'email' => 'mika@student.rs',
            'password' => Hash::make('password'),
            'uloga' => 'STUDENT',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.ime', $user->ime)
            ->assertJsonPath('user.prezime', $user->prezime)
            ->assertJsonPath('user.uloga', $user->uloga);
    }

    public function test_logout_deletes_current_access_token(): void
    {
        $user = User::create([
            'ime' => 'Pera',
            'prezime' => 'Peric',
            'email' => 'pera@student.rs',
            'password' => Hash::make('password'),
            'uloga' => 'STUDENT',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Uspešno ste se odjavili.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }
}