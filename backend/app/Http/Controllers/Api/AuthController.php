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
            // Keamanan: Hapus email, IP, password, dan token.
            Log::warning('Autentikasi login pengguna gagal', [
                'action'    => 'auth_login',
                'component' => 'auth',
                'actor'     => [
                    'type' => 'guest',
                ],
                'reason'    => 'Kombinasi kredensial email atau kata sandi tidak valid',
            ]);

            return response()->json([
                'message' => 'Email atau password salah.',
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        // Log terstruktur: Login Berhasil (Level: INFO)
        // Keamanan: Konteks actor aman (user_id saja), tanpa email, IP, atau token plain text.
        Log::info('Pengguna berhasil melakukan login', [
            'action'    => 'auth_login',
            'component' => 'auth',
            'actor'     => [
                'type' => 'user',
                'id'   => $user->id,
            ],
            'reason'    => 'Kredensial valid dan token akses berhasil diterbitkan',
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
            'action'    => 'auth_logout',
            'component' => 'auth',
            'actor'     => [
                'type' => 'user',
                'id'   => $user?->id,
            ],
            'reason'    => 'Token akses berhasil dicabut dan sesi pengguna diakhiri',
        ]);

        return response()->json([
            'message' => 'Logout berhasil.',
        ], 200);
    }
}
