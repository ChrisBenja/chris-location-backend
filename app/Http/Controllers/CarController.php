<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;

class CarController extends Controller
{
    /**
     * Afficher la liste des véhicules.
     *
     * Accessible publiquement.
     */
    public function index(Request $request)
    {
        $query = Car::query();

        // Recherche par marque ou modèle
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        // Filtre par marque
        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        // Filtre par type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filtre par prix maximum
        if ($request->filled('max_price')) {
            $query->where('price_per_day', '<=', $request->max_price);
        }

        // Filtre par disponibilité
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $cars = $query
            ->orderBy('created_at', 'desc')
            ->paginate(9);

        return response()->json($cars);
    }

    /**
     * Afficher un véhicule.
     *
     * Accessible publiquement.
     */
    public function show(Car $car)
    {
        return response()->json([
            'car' => $car,
        ]);
    }

    /**
     * Ajouter un véhicule.
     *
     * Administrateur uniquement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'type' => [
                'required',
                'string',
                'in:Citadine,Berline,SUV,Coupé,Utilitaire'
            ],
            'seats' => ['required', 'integer', 'min:1', 'max:20'],
            'price_per_day' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'status' => [
                'required',
                'string',
                'in:available,unavailable'
            ],
        ]);

        $car = Car::create($validated);

        return response()->json([
            'message' => 'Véhicule créé avec succès.',
            'car' => $car,
        ], 201);
    }

    /**
     * Modifier un véhicule.
     *
     * Administrateur uniquement.
     */
    public function update(Request $request, Car $car)
    {
        $validated = $request->validate([
            'brand' => ['sometimes', 'required', 'string', 'max:100'],
            'model' => ['sometimes', 'required', 'string', 'max:100'],
            'year' => ['sometimes', 'required', 'integer', 'min:1900', 'max:2100'],
            'type' => [
                'sometimes',
                'required',
                'string',
                'in:Citadine,Berline,SUV,Coupé,Utilitaire'
            ],
            'seats' => ['sometimes', 'required', 'integer', 'min:1', 'max:20'],
            'price_per_day' => ['sometimes', 'required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'status' => [
                'sometimes',
                'required',
                'string',
                'in:available,unavailable'
            ],
        ]);

        $car->update($validated);

        return response()->json([
            'message' => 'Véhicule modifié avec succès.',
            'car' => $car->fresh(),
        ]);
    }

    /**
     * Supprimer un véhicule.
     *
     * Administrateur uniquement.
     */
    public function destroy(Car $car)
    {
        // On vérifie s'il existe des réservations liées.
        if ($car->reservations()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce véhicule car il possède des réservations.'
            ], 409);
        }

        $car->delete();

        return response()->json([
            'message' => 'Véhicule supprimé avec succès.',
        ]);
    }
}