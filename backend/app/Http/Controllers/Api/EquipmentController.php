<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'category'      => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:equipments,serial_number',
            'condition'     => ['required', Rule::in(['baik', 'rusak_ringan', 'rusak_berat'])],
            'quantity'      => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            // Log terstruktur: Validasi Gagal (Level: WARNING)
            // Keamanan: Hapus email, IP, token, dan data pribadi.
            Log::warning('Validasi data penambahan alat laboratorium gagal', [
                'action'        => 'equipment_create',
                'component'     => 'equipment',
                'actor'         => [
                    'type' => 'user',
                    'id'   => $request->user()?->id,
                ],
                'failed_fields' => array_keys($validator->errors()->toArray()),
                'reason'        => 'Aturan validasi atribut peralatan tidak terpenuhi',
            ]);

            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $equipment = Equipment::create($validator->validated());

            // Log terstruktur: Menyimpan Data Berhasil (Level: INFO)
            // Keamanan: Actor aman (ID user), equipment_id tercatat, tanpa data pribadi.
            Log::info('Data alat laboratorium berhasil disimpan', [
                'action'       => 'equipment_create',
                'component'    => 'equipment',
                'actor'        => [
                    'type' => 'user',
                    'id'   => $request->user()?->id,
                ],
                'equipment_id' => $equipment->id,
                'reason'       => 'Peralatan baru berhasil terdaftar di basis data',
            ]);

            return response()->json([
                'message' => 'Peralatan berhasil ditambahkan.',
                'data'    => $equipment,
            ], 201);
        } catch (\Throwable $e) {
            // Log terstruktur: Gagal Basis Data (Level: ERROR)
            // Keamanan: Tidak mengekspos raw exception message yang dapat membocorkan kredensial DB.
            Log::error('Gagal menyimpan data alat laboratorium ke basis data', [
                'action'    => 'equipment_create',
                'component' => 'database',
                'actor'     => [
                    'type' => 'user',
                    'id'   => $request->user()?->id,
                ],
                'reason'    => 'Terjadi kesalahan sistem saat menyimpan data ke basis data',
            ]);

            throw $e;
        }
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
        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            // Log terstruktur: Validasi Pembaruan Gagal (Level: WARNING)
            Log::warning('Validasi data pembaruan alat laboratorium gagal', [
                'action'        => 'equipment_update',
                'component'     => 'equipment',
                'actor'         => [
                    'type' => 'user',
                    'id'   => $request->user()?->id,
                ],
                'equipment_id'  => $equipment->id,
                'failed_fields' => array_keys($validator->errors()->toArray()),
                'reason'        => 'Aturan validasi atribut pembaruan tidak terpenuhi',
            ]);

            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $equipment->update($validator->validated());

            // Log terstruktur: Pembaruan Berhasil (Level: INFO)
            Log::info('Data alat laboratorium berhasil diperbarui', [
                'action'       => 'equipment_update',
                'component'    => 'equipment',
                'actor'        => [
                    'type' => 'user',
                    'id'   => $request->user()?->id,
                ],
                'equipment_id' => $equipment->id,
                'reason'       => 'Perubahan data peralatan berhasil disimpan ke basis data',
            ]);

            return response()->json([
                'message' => 'Peralatan berhasil diperbarui.',
                'data'    => $equipment->fresh(),
            ], 200);
        } catch (\Throwable $e) {
            // Log terstruktur: Gagal Basis Data (Level: ERROR)
            Log::error('Gagal memperbarui data alat laboratorium di basis data', [
                'action'       => 'equipment_update',
                'component'    => 'database',
                'actor'        => [
                    'type' => 'user',
                    'id'   => $request->user()?->id,
                ],
                'equipment_id' => $equipment->id,
                'reason'       => 'Terjadi kesalahan sistem saat memperbarui data di basis data',
            ]);

            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Equipment $equipment): JsonResponse
    {
        try {
            $id = $equipment->id;
            $equipment->delete();

            // Log terstruktur: Penghapusan Berhasil (Level: INFO)
            Log::info('Data alat laboratorium berhasil dihapus', [
                'action'       => 'equipment_delete',
                'component'    => 'equipment',
                'actor'        => [
                    'type' => 'user',
                    'id'   => $request->user()?->id,
                ],
                'equipment_id' => $id,
                'reason'       => 'Data peralatan berhasil dihapus dari basis data',
            ]);

            return response()->json([
                'message' => 'Peralatan berhasil dihapus.',
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus data alat laboratorium dari basis data', [
                'action'       => 'equipment_delete',
                'component'    => 'database',
                'actor'        => [
                    'type' => 'user',
                    'id'   => $request->user()?->id,
                ],
                'equipment_id' => $equipment->id,
                'reason'       => 'Terjadi kesalahan sistem saat menghapus data dari basis data',
            ]);

            throw $e;
        }
    }
}
