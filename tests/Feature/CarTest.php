<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CarTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_visiteur_peut_consulter_la_liste_des_vehicules(): void
    {
        Car::create([
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2024,
            'type' => 'Berline',
            'seats' => 5,
            'price_per_day' => 350,
            'description' => 'Véhicule confortable.',
            'status' => 'available',
        ]);

        $response = $this->getJson('/api/cars');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);

        $response->assertJsonFragment([
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ]);
    }

    public function test_un_visiteur_peut_consulter_un_vehicule(): void
    {
        $car = Car::create([
            'brand' => 'BMW',
            'model' => 'Série 3',
            'year' => 2023,
            'type' => 'Berline',
            'seats' => 5,
            'price_per_day' => 600,
            'description' => 'Berline élégante.',
            'status' => 'available',
        ]);

        $response = $this->getJson('/api/cars/' . $car->id);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'brand' => 'BMW',
                'model' => 'Série 3',
            ]);
    }

    public function test_un_admin_peut_creer_un_vehicule(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin@test.com',
            'password' => 'Password123!',
            'role' => 'admin',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/cars', [
            'brand' => 'Mercedes',
            'model' => 'Classe C',
            'year' => 2024,
            'type' => 'Berline',
            'seats' => 5,
            'price_per_day' => 500,
            'description' => 'Véhicule de test.',
            'status' => 'available',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonFragment([
                'brand' => 'Mercedes',
                'model' => 'Classe C',
            ]);

        $this->assertDatabaseHas('cars', [
            'brand' => 'Mercedes',
            'model' => 'Classe C',
        ]);
    }

    public function test_un_client_ne_peut_pas_creer_un_vehicule(): void
    {
        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Test',
            'email' => 'client@test.com',
            'password' => 'Password123!',
            'role' => 'client',
        ]);

        Sanctum::actingAs($client);

        $response = $this->postJson('/api/cars', [
            'brand' => 'Audi',
            'model' => 'A4',
            'year' => 2024,
            'type' => 'Berline',
            'seats' => 5,
            'price_per_day' => 500,
            'status' => 'available',
        ]);

        $response->assertStatus(403);
    }

    public function test_la_creation_d_un_vehicule_refuse_des_donnees_invalides(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin@test.com',
            'password' => 'Password123!',
            'role' => 'admin',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/cars', [
            'brand' => '',
            'model' => '',
            'year' => 1800,
            'type' => 'Avion',
            'seats' => 0,
            'price_per_day' => -100,
            'status' => 'unknown',
        ]);

        $response->assertStatus(422);
    }
}