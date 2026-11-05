<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Handle user login and issue a Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            // Log terstruktur: Autentikasi Gagal (Level: WARNING)
            // Keamanan: TIDAK PERNAH mencatat password input.
            Log::warning('Autentikasi login pengguna gagal', [
                'action' => 'auth_login',
                'actor'  => [
                    'attempted_email' => $request->input('email'),
                    'ip'              => $request->ip(),
                ],
                'reason' => 'Email atau password yang dimasukkan salah',
            ]);

            return response()->json([
                'message' => 'Email atau password salah.',
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        // Log terstruktur: Login Berhasil (Level: INFO)
        // Keamanan: TIDAK PERNAH mencatat token string.
        Log::info('Pengguna berhasil melakukan login', [
            'action' => 'auth_login',
            'actor'  => [
                'user_id' => $user->id,
                'email'   => $user->email,
                'ip'      => $request->ip(),
            ],
            'reason' => 'Kredensial valid dan token akses baru berhasil diterbitkan',
        ]);

        return response()->json([
            'message' => 'Login berhasil.',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ], 200);
    }

    /**
     * Revoke the current user's token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $user  = $request->user();
        $token = $user?->currentAccessToken();

        // In production this is a PersonalAccessToken with delete();
        // In tests with actingAs() it may be a TransientToken without delete()
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        // Log terstruktur: Logout Berhasil (Level: INFO)
        Log::info('Pengguna berhasil melakukan logout', [
            'action' => 'auth_logout',
            'actor'  => [
                'user_id' => $user?->id,
                'email'   => $user?->email,
                'ip'      => $request->ip(),
            ],
            'reason' => 'Token akses berhasil dicabut dan sesi pengguna diakhiri',
        ]);

        return response()->json([
            'message' => 'Logout berhasil.',
        ], 200);
    }
}
