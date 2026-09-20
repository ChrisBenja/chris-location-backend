<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Administrateur
        |--------------------------------------------------------------------------
        */

        User::updateOrCreate(
            [
                'email' => 'admin@chris-location.com',
            ],
            [
                'first_name' => 'Admin',
                'last_name' => 'Chris-Location',
                'password' => Hash::make('Admin1234!'),
                'role' => 'admin',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Client
        |--------------------------------------------------------------------------
        */

        User::updateOrCreate(
            [
                'email' => 'client@chris-location.com',
            ],
            [
                'first_name' => 'Client',
                'last_name' => 'Test',
                'password' => Hash::make('Client1234!'),
                'role' => 'client',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Véhicules
        |--------------------------------------------------------------------------
        */

        Car::updateOrCreate(
            [
                'brand' => 'Toyota',
                'model' => 'Corolla',
            ],
            [
                'year' => 2024,
                'type' => 'Berline',
                'seats' => 5,
                'price_per_day' => 350,
                'description' => 'Véhicule confortable et économique, idéal pour les déplacements en ville et les longs trajets.',
                'image' => null,
                'status' => 'available',
            ]
        );

        Car::updateOrCreate(
            [
                'brand' => 'BMW',
                'model' => 'Série 3',
            ],
            [
                'year' => 2023,
                'type' => 'Berline',
                'seats' => 5,
                'price_per_day' => 600,
                'description' => 'Berline élégante et performante offrant un excellent confort de conduite.',
                'image' => null,
                'status' => 'available',
            ]
        );

        Car::updateOrCreate(
            [
                'brand' => 'Peugeot',
                'model' => '208',
            ],
            [
                'year' => 2024,
                'type' => 'Citadine',
                'seats' => 5,
                'price_per_day' => 300,
                'description' => 'Citadine moderne, pratique et économique pour les déplacements quotidiens.',
                'image' => null,
                'status' => 'unavailable',
            ]
        );
    }
}