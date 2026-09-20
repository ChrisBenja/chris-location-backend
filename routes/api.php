<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Authentification
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', [AuthController::class, 'me']);

});


/*
|--------------------------------------------------------------------------
| Véhicules - accès public
|--------------------------------------------------------------------------
*/

Route::get('/cars', [CarController::class, 'index']);

Route::get('/cars/{car}', [CarController::class, 'show']);


/*
|--------------------------------------------------------------------------
| Administration - véhicules
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'admin'
])->group(function () {

    Route::post('/cars', [CarController::class, 'store']);

    Route::put('/cars/{car}', [CarController::class, 'update']);

    Route::delete('/cars/{car}', [CarController::class, 'destroy']);

});


/*
|--------------------------------------------------------------------------
| Réservations
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/reservations', [
        ReservationController::class,
        'store'
    ]);

    Route::get('/reservations', [
        ReservationController::class,
        'index'
    ]);

    Route::put('/reservations/{reservation}', [
        ReservationController::class,
        'update'
    ]);

    Route::delete('/reservations/{reservation}', [
        ReservationController::class,
        'destroy'
    ]);

});