<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * Afficher les réservations.
     *
     * Client :
     * uniquement ses propres réservations.
     *
     * Admin :
     * toutes les réservations.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            $reservations = Reservation::with([
                'user:id,first_name,last_name,email',
                'car:id,brand,model,year,price_per_day'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        } else {
            $reservations = Reservation::with([
                'car:id,brand,model,year,price_per_day'
            ])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        }

        return response()->json($reservations);
    }


    /**
     * Créer une réservation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_id' => ['required', 'integer', 'exists:cars,id'],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after:start_date',
            ],
        ]);

        $car = Car::findOrFail($validated['car_id']);

        /*
        |--------------------------------------------------------------------------
        | Vérification de la disponibilité générale
        |--------------------------------------------------------------------------
        */

        if ($car->status !== 'available') {
            return response()->json([
                'message' => 'Ce véhicule n’est actuellement pas disponible.'
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | Conversion des dates
        |--------------------------------------------------------------------------
        */

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        /*
        |--------------------------------------------------------------------------
        | Vérification d'une date passée
        |--------------------------------------------------------------------------
        */

        if ($startDate->startOfDay()->lt(now()->startOfDay())) {
            return response()->json([
                'message' => 'La date de début ne peut pas être dans le passé.'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Recherche d'un chevauchement
        |--------------------------------------------------------------------------
        |
        | Deux réservations se chevauchent lorsque :
        |
        | nouvelle début < réservation existante fin
        | ET
        | nouvelle fin > réservation existante début
        |
        */

        $overlap = Reservation::where('car_id', $car->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query
                    ->where('start_date', '<', $endDate->toDateString())
                    ->where('end_date', '>', $startDate->toDateString());
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'message' => 'Le véhicule est déjà réservé pendant cette période.'
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | Calcul du nombre de jours
        |--------------------------------------------------------------------------
        */

        $numberOfDays = $startDate->diffInDays($endDate);

        if ($numberOfDays < 1) {
            return response()->json([
                'message' => 'La réservation doit durer au minimum un jour.'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Calcul du prix total
        |--------------------------------------------------------------------------
        |
        | Le prix est calculé côté serveur.
        | Le frontend ne peut donc pas imposer son propre prix.
        |
        */

        $totalPrice = $numberOfDays * (float) $car->price_per_day;

        /*
        |--------------------------------------------------------------------------
        | Création
        |--------------------------------------------------------------------------
        */

        $reservation = Reservation::create([
            'user_id' => $request->user()->id,
            'car_id' => $car->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_price' => $totalPrice,
            'status' => 'pending',
        ]);

        $reservation->load([
            'car:id,brand,model,year,price_per_day',
        ]);

        return response()->json([
            'message' => 'Réservation créée avec succès.',
            'reservation' => $reservation,
            'number_of_days' => $numberOfDays,
            'total_price' => $totalPrice,
        ], 201);
    }


    /**
     * Modifier une réservation.
     *
     * Admin :
     * peut confirmer ou refuser.
     *
     * Client :
     * peut annuler sa réservation selon les règles.
     */
    public function update(Request $request, Reservation $reservation)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'admin') {

            $validated = $request->validate([
                'status' => [
                    'required',
                    'string',
                    'in:pending,confirmed,rejected,cancelled',
                ],
            ]);

            $reservation->update([
                'status' => $validated['status'],
            ]);

            $reservation->load([
                'user:id,first_name,last_name,email',
                'car:id,brand,model,year,price_per_day',
            ]);

            return response()->json([
                'message' => 'Statut de la réservation modifié avec succès.',
                'reservation' => $reservation,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CLIENT
        |--------------------------------------------------------------------------
        */

        if ($reservation->user_id !== $user->id) {
            return response()->json([
                'message' => 'Vous n’êtes pas autorisé à modifier cette réservation.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Annulation
        |--------------------------------------------------------------------------
        */

        if ($request->input('status') !== 'cancelled') {
            return response()->json([
                'message' => 'Un client peut uniquement annuler une réservation.'
            ], 422);
        }

        if (in_array($reservation->status, ['cancelled', 'rejected'])) {
            return response()->json([
                'message' => 'Cette réservation ne peut plus être annulée.'
            ], 422);
        }

        /*
        | Exemple de règle :
        | une réservation ne peut être annulée
        | que si elle n'a pas encore commencé.
        */

        if (Carbon::parse($reservation->start_date)->startOfDay()->lte(now()->startOfDay())) {
            return response()->json([
                'message' => 'Cette réservation ne peut plus être annulée car elle a commencé ou doit commencer aujourd’hui.'
            ], 422);
        }

        $reservation->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'message' => 'Réservation annulée avec succès.',
            'reservation' => $reservation->fresh(),
        ]);
    }


    /**
     * Supprimer/annuler une réservation.
     *
     * Nous utilisons cette route principalement pour permettre
     * l'annulation par l'utilisateur propriétaire.
     */
    public function destroy(Request $request, Reservation $reservation)
    {
        $user = $request->user();

        if ($reservation->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'message' => 'Vous n’êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        if ($reservation->status === 'cancelled') {
            return response()->json([
                'message' => 'Cette réservation est déjà annulée.'
            ], 422);
        }

        if ($reservation->status === 'rejected') {
            return response()->json([
                'message' => 'Cette réservation a déjà été refusée.'
            ], 422);
        }

        if ($user->role !== 'admin') {

            if (Carbon::parse($reservation->start_date)->startOfDay()->lte(now()->startOfDay())) {
                return response()->json([
                    'message' => 'Cette réservation ne peut plus être annulée.'
                ], 422);
            }
        }

        $reservation->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'message' => 'Réservation annulée avec succès.',
            'reservation' => $reservation->fresh(),
        ]);
    }
}