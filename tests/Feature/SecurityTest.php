<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_non_connecte_ne_peut_pas_creer_une_reservation(): void
    {
        $car = Car::create([
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2024,
            'type' => 'Berline',
            'seats' => 5,
            'price_per_day' => 350,
            'description' => 'Véhicule de test.',
            'status' => 'available',
        ]);

        $response = $this->postJson('/api/reservations', [
            'car_id' => $car->id,
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-23',
        ]);

        $response->assertStatus(401);
    }

    public function test_un_client_ne_peut_pas_acceder_aux_routes_admin(): void
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
            'description' => 'Test sécurité.',
            'status' => 'available',
        ]);

        $response->assertStatus(403);
    }

    public function test_un_utilisateur_non_connecte_ne_peut_pas_se_deconnecter(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    public function test_un_client_ne_peut_pas_supprimer_un_vehicule(): void
    {
        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Test',
            'email' => 'client@test.com',
            'password' => 'Password123!',
            'role' => 'client',
        ]);

        $car = Car::create([
            'brand' => 'BMW',
            'model' => 'Série 3',
            'year' => 2023,
            'type' => 'Berline',
            'seats' => 5,
            'price_per_day' => 600,
            'description' => 'Véhicule de test.',
            'status' => 'available',
        ]);

        Sanctum::actingAs($client);

        $response = $this->deleteJson('/api/cars/' . $car->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
        ]);
    }
}