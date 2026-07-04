<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
    $request->validate([
        'correo' => 'required|email',
        'clave'  => 'required|string',
    ]);

    $usuario = Usuario::where('correo', $request->correo)->first();

    if (! $usuario || ! Hash::check($request->clave, $usuario->clave)) {
        throw ValidationException::withMessages([
            'correo' => ['Las credenciales no son correctas.'],
        ]);
    }

    $token = $usuario->createToken('mobile')->plainTextToken;

    return response()->json([
        'token'   => $token,
        'usuario' => $usuario,
    ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function updatePushToken(Request $request): JsonResponse
    {
        $request->validate([
            'expo_push_token' => 'required|string',
        ]);

        $request->user()->update([
            'expo_push_token' => $request->expo_push_token,
        ]);

        return response()->json(['message' => 'Token registrado correctamente.']);
    }
    public function postularRepartidor(Request $request): JsonResponse
    {
        $request->validate([
            'facultad' => 'required|string|max:100',
        ]);

        $usuario = $request->user();

        if (!in_array($usuario->estado_repartidor, ['no_postulado', 'rechazado'])) {
            return response()->json([
                'message' => 'No puedes postular en tu estado actual.',
            ], 422);
        }

        $usuario->update([
            'facultad' => $request->facultad,
            'estado_repartidor' => 'pendiente',
        ]);

        return response()->json([
            'message' => 'Postulación enviada correctamente.',
            'usuario' => $usuario->fresh(),
        ]);
    }
    public function misLiquidaciones(Request $request): JsonResponse
    {
        $liquidaciones = $request->user()
            ->liquidaciones()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($liquidaciones);
    }
}
