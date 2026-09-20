<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private function createClient(string $email): User
    {
        return User::create([
            'first_name' => 'Client',
            'last_name' => 'Test',
            'email' => $email,
            'password' => 'Password123!',
            'role' => 'client',
        ]);
    }

    private function createAdmin(): User
    {
        return User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin@test.com',
            'password' => 'Password123!',
            'role' => 'admin',
        ]);
    }

    private function createAvailableCar(): Car
    {
        return Car::create([
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2024,
            'type' => 'Berline',
            'seats' => 5,
            'price_per_day' => 350,
            'description' => 'Véhicule de test.',
            'status' => 'available',
        ]);
    }

    public function test_un_client_peut_creer_une_reservation(): void
    {
        $client = $this->createClient('client1@test.com');
        $car = $this->createAvailableCar();

        Sanctum::actingAs($client);

        $response = $this->postJson('/api/reservations', [
            'car_id' => $car->id,
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-23',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonFragment([
                'number_of_days' => 3,
            ]);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $client->id,
            'car_id' => $car->id,
            'status' => 'pending',
            'total_price' => 1050,
        ]);
    }

    public function test_un_vehicule_indisponible_ne_peut_pas_etre_reserve(): void
    {
        $client = $this->createClient('client1@test.com');

        $car = Car::create([
            'brand' => 'Peugeot',
            'model' => '208',
            'year' => 2024,
            'type' => 'Citadine',
            'seats' => 5,
            'price_per_day' => 300,
            'description' => 'Véhicule indisponible.',
            'status' => 'unavailable',
        ]);

        Sanctum::actingAs($client);

        $response = $this->postJson('/api/reservations', [
            'car_id' => $car->id,
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-23',
        ]);

        $response->assertStatus(409);
    }

    public function test_une_reservation_qui_se_chevauche_est_refusee(): void
    {
        $client1 = $this->createClient('client1@test.com');
        $client2 = $this->createClient('client2@test.com');

        $car = $this->createAvailableCar();

        Reservation::create([
            'user_id' => $client1->id,
            'car_id' => $car->id,
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-23',
            'total_price' => 1050,
            'status' => 'confirmed',
        ]);

        Sanctum::actingAs($client2);

        $response = $this->postJson('/api/reservations', [
            'car_id' => $car->id,
            'start_date' => '2026-10-22',
            'end_date' => '2026-10-25',
        ]);

        $response->assertStatus(409);
    }

    public function test_un_client_ne_voit_que_ses_reservations(): void
    {
        $client1 = $this->createClient('client1@test.com');
        $client2 = $this->createClient('client2@test.com');

        $car = $this->createAvailableCar();

        $reservation1 = Reservation::create([
            'user_id' => $client1->id,
            'car_id' => $car->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
            'total_price' => 700,
            'status' => 'pending',
        ]);

        Reservation::create([
            'user_id' => $client2->id,
            'car_id' => $car->id,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-03',
            'total_price' => 700,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($client1);

        $response = $this->getJson('/api/reservations');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $reservation1->id,
        ]);

        $response->assertJsonMissing([
            'start_date' => '2026-12-01',
        ]);
    }

    public function test_un_client_ne_peut_pas_modifier_la_reservation_d_un_autre_client(): void
    {
        $client1 = $this->createClient('client1@test.com');
        $client2 = $this->createClient('client2@test.com');

        $car = $this->createAvailableCar();

        $reservation = Reservation::create([
            'user_id' => $client1->id,
            'car_id' => $car->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
            'total_price' => 700,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($client2);

        $response = $this->putJson(
            '/api/reservations/' . $reservation->id,
            [
                'status' => 'cancelled',
            ]
        );

        $response->assertStatus(403);
    }

    public function test_un_client_ne_peut_pas_confirmer_une_reservation(): void
    {
        $client = $this->createClient('client1@test.com');

        $car = $this->createAvailableCar();

        $reservation = Reservation::create([
            'user_id' => $client->id,
            'car_id' => $car->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
            'total_price' => 700,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($client);

        $response = $this->putJson(
            '/api/reservations/' . $reservation->id,
            [
                'status' => 'confirmed',
            ]
        );

        $response->assertStatus(422);
    }

    public function test_un_admin_peut_confirmer_une_reservation(): void
    {
        $client = $this->createClient('client1@test.com');
        $admin = $this->createAdmin();

        $car = $this->createAvailableCar();

        $reservation = Reservation::create([
            'user_id' => $client->id,
            'car_id' => $car->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
            'total_price' => 700,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson(
            '/api/reservations/' . $reservation->id,
            [
                'status' => 'confirmed',
            ]
        );

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'status' => 'confirmed',
            ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
        ]);
    }
}