<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $equipments = Equipment::latest()->get();

        return response()->json([
            'message' => 'Data peralatan berhasil dimuat.',
            'data' => $equipments,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'category'      => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:equipments,serial_number',
            'condition'     => ['required', Rule::in(['baik', 'rusak_ringan', 'rusak_berat'])],
            'quantity'      => 'required|integer|min:0',
        ]);

        $equipment = Equipment::create($validated);

        return response()->json([
            'message' => 'Peralatan berhasil ditambahkan.',
            'data' => $equipment,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Equipment $equipment): JsonResponse
    {
        return response()->json([
            'message' => 'Detail peralatan berhasil dimuat.',
            'data' => $equipment,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Equipment $equipment): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'category'      => 'required|string|max:255',
            'serial_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('equipments', 'serial_number')->ignore($equipment->id),
            ],
            'condition'     => ['required', Rule::in(['baik', 'rusak_ringan', 'rusak_berat'])],
            'quantity'      => 'required|integer|min:0',
        ]);

        $equipment->update($validated);

        return response()->json([
            'message' => 'Peralatan berhasil diperbarui.',
            'data' => $equipment->fresh(),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Equipment $equipment): JsonResponse
    {
        $equipment->delete();

        return response()->json([
            'message' => 'Peralatan berhasil dihapus.',
        ], 200);
    }
}
