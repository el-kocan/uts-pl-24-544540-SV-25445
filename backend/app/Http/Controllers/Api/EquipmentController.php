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
            // Log terstruktur alur penting: Validasi Gagal (Level: WARNING)
            // Memuat konteks lengkap: Siapa (actor), Apa (action), Sebab (reason & errors).
            // TIDAK mencatat password, token, atau data pribadi sensitif.
            Log::warning('Validasi penambahan alat laboratorium gagal', [
                'action'        => 'equipment_store_validation',
                'actor'         => [
                    'user_id' => $request->user()?->id,
                    'email'   => $request->user()?->email,
                    'ip'      => $request->ip(),
                ],
                'failed_fields' => array_keys($validator->errors()->toArray()),
                'errors'        => $validator->errors()->toArray(),
                'reason'        => 'Data input tidak memenuhi aturan validasi sistem',
            ]);

            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $equipment = Equipment::create($validator->validated());

            // Log terstruktur alur penting: Menyimpan Data (Level: INFO)
            // Memuat konteks: Siapa (actor), Apa (action), Sebab (reason & equipment data).
            Log::info('Data alat laboratorium berhasil disimpan ke basis data', [
                'action'    => 'equipment_store',
                'actor'     => [
                    'user_id' => $request->user()?->id,
                    'email'   => $request->user()?->email,
                    'ip'      => $request->ip(),
                ],
                'equipment' => [
                    'id'            => $equipment->id,
                    'name'          => $equipment->name,
                    'category'      => $equipment->category,
                    'serial_number' => $equipment->serial_number,
                    'condition'     => $equipment->condition,
                    'quantity'      => $equipment->quantity,
                ],
                'reason'    => 'Validasi berhasil dan data tersimpan ke tabel equipments',
            ]);

            return response()->json([
                'message' => 'Peralatan berhasil ditambahkan.',
                'data'    => $equipment,
            ], 201);
        } catch (\Throwable $e) {
            // Log terstruktur alur penting: Gagal Menghubungi Basis Data (Level: ERROR)
            Log::error('Gagal menyimpan data alat laboratorium ke basis data', [
                'action'    => 'equipment_store_database',
                'actor'     => [
                    'user_id' => $request->user()?->id,
                    'email'   => $request->user()?->email,
                    'ip'      => $request->ip(),
                ],
                'component' => 'database',
                'reason'    => $e->getMessage(),
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
            // Log terstruktur alur penting: Validasi Pembaruan Gagal (Level: WARNING)
            Log::warning('Validasi pembaruan alat laboratorium gagal', [
                'action'        => 'equipment_update_validation',
                'actor'         => [
                    'user_id' => $request->user()?->id,
                    'email'   => $request->user()?->email,
                    'ip'      => $request->ip(),
                ],
                'equipment_id'  => $equipment->id,
                'failed_fields' => array_keys($validator->errors()->toArray()),
                'errors'        => $validator->errors()->toArray(),
                'reason'        => 'Data input pembaruan tidak memenuhi aturan validasi sistem',
            ]);

            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $equipment->update($validator->validated());

            // Log terstruktur: Pembaruan Data Berhasil (Level: INFO)
            Log::info('Data alat laboratorium berhasil diperbarui', [
                'action'       => 'equipment_update',
                'actor'        => [
                    'user_id' => $request->user()?->id,
                    'email'   => $request->user()?->email,
                    'ip'      => $request->ip(),
                ],
                'equipment_id' => $equipment->id,
                'reason'       => 'Pembaruan data berhasil disimpan ke basis data',
            ]);

            return response()->json([
                'message' => 'Peralatan berhasil diperbarui.',
                'data'    => $equipment->fresh(),
            ], 200);
        } catch (\Throwable $e) {
            // Log terstruktur: Gagal Basis Data saat Update (Level: ERROR)
            Log::error('Gagal memperbarui data alat laboratorium di basis data', [
                'action'       => 'equipment_update_database',
                'actor'        => [
                    'user_id' => $request->user()?->id,
                    'email'   => $request->user()?->email,
                    'ip'      => $request->ip(),
                ],
                'equipment_id' => $equipment->id,
                'component'    => 'database',
                'reason'       => $e->getMessage(),
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
            $serial = $equipment->serial_number;
            $equipment->delete();

            // Log terstruktur: Penghapusan Data Berhasil (Level: INFO)
            Log::info('Data alat laboratorium berhasil dihapus', [
                'action'        => 'equipment_destroy',
                'actor'         => [
                    'user_id' => $request->user()?->id,
                    'email'   => $request->user()?->email,
                    'ip'      => $request->ip(),
                ],
                'equipment_id'  => $id,
                'serial_number' => $serial,
                'reason'        => 'Permintaan penghapusan peralatan diproses dan data dihapus dari basis data',
            ]);

            return response()->json([
                'message' => 'Peralatan berhasil dihapus.',
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus data alat laboratorium dari basis data', [
                'action'       => 'equipment_destroy_database',
                'actor'        => [
                    'user_id' => $request->user()?->id,
                    'email'   => $request->user()?->email,
                    'ip'      => $request->ip(),
                ],
                'equipment_id' => $equipment->id,
                'component'    => 'database',
                'reason'       => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
