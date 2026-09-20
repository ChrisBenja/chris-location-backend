<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_peut_s_inscrire(): void
    {
        $response = $this->postJson('/api/register', [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jean@test.com',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'role' => 'client',
        ]);
    }

    public function test_un_utilisateur_peut_se_connecter(): void
    {
        $user = User::create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'client',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'jean@test.com',
            'password' => 'Password123!',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);
    }

    public function test_un_utilisateur_ne_peut_pas_se_connecter_avec_un_mauvais_mot_de_passe(): void
    {
        User::create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'client',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'jean@test.com',
            'password' => 'MauvaisMotDePasse!',
        ]);

        $response->assertStatus(422);
    }

    public function test_un_utilisateur_connecte_peut_consulter_son_profil(): void
    {
        $user = User::create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'client',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'user',
            ]);
    }

    public function test_un_utilisateur_non_connecte_ne_peut_pas_consulter_son_profil(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }
}