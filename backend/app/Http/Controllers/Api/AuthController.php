<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Login/registro con Microsoft (Azure AD / Entra ID).
    // El móvil hace el flujo OAuth y nos manda el access_token de Microsoft Graph;
    // nosotros lo validamos consultando /me directamente a Microsoft (así no
    // tenemos que validar la firma del JWT nosotros mismos).
    public function microsoft(Request $request): JsonResponse
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        $graphResponse = Http::withToken($request->access_token)
            ->get('https://graph.microsoft.com/v1.0/me');

        if ($graphResponse->failed()) {
            return response()->json([
                'message' => 'No se pudo validar la cuenta de Microsoft.',
            ], 401);
        }

        $perfil = $graphResponse->json();
        $correo = $perfil['mail'] ?? $perfil['userPrincipalName'] ?? null;
        $nombre = $perfil['displayName'] ?? 'Usuario ULEAM';
        $microsoftId = $perfil['id'] ?? null;

        if (! $correo || ! $microsoftId) {
            return response()->json([
                'message' => 'La cuenta de Microsoft no tiene correo disponible.',
            ], 422);
        }

        $usuario = Usuario::where('microsoft_id', $microsoftId)
            ->orWhere('correo', $correo)
            ->first();

        if ($usuario) {
            // Por si el usuario ya existía (ej. registrado antes por correo/clave)
            // pero es la primera vez que entra con Microsoft.
            if (! $usuario->microsoft_id) {
                $usuario->update(['microsoft_id' => $microsoftId]);
            }
        } else {
            $usuario = Usuario::create([
                'nombre_completo'   => $nombre,
                'correo'            => $correo,
                'microsoft_id'      => $microsoftId,
                'rol'               => 'cliente',
                'estado_repartidor' => 'no_postulado',
                'balance'           => 0,
            ]);
        }

        $token = $usuario->createToken('mobile')->plainTextToken;

        return response()->json([
            'token'   => $token,
            'usuario' => $usuario,
        ]);
    }

    // Completa la cédula tras el primer login con Microsoft (la BD la sigue
    // exigiendo para el resto de la lógica de la app: pedidos, liquidaciones, etc.)
    public function completarPerfil(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $request->validate([
            'cedula' => [
                'required',
                'string',
                'size:10',
                Rule::unique('usuarios', 'cedula')->ignore($usuario->id),
            ],
        ]);

        $usuario->update(['cedula' => $request->cedula]);

        return response()->json([
            'usuario' => $usuario->fresh(),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'cedula' => 'required|string|size:10|unique:usuarios,cedula',
            'nombre_completo' => 'required|string|max:100',
            'correo' => 'required|email|unique:usuarios,correo',
            'clave' => 'required|string|min:6|confirmed',
        ]);

        $usuario = Usuario::create([
            'cedula'            => $request->cedula,
            'nombre_completo'   => $request->nombre_completo,
            'correo'            => $request->correo,
            'clave'             => $request->clave,
            'rol'               => 'cliente',
            'estado_repartidor' => 'no_postulado',
            'balance'           => 0,
        ]);

        $token = $usuario->createToken('mobile')->plainTextToken;

                return response()->json([
                    'token'   => $token,
                    'usuario' => $usuario,
                ]);
            }

            // Login con email/clave tradicional
            public function login(Request $request): JsonResponse
            {
                $request->validate([
                    'correo' => 'required|email',
                    'clave'  => 'required|string',
                    'expo_push_token' => 'nullable|string', // token push opcional
                ]);

                $usuario = Usuario::where('correo', $request->correo)->first();

                if (! $usuario || ! Hash::check($request->clave, $usuario->clave)) {
                    throw ValidationException::withMessages([
                        'correo' => ['Las credenciales no son correctas.'],
                    ]);
                }

                // Actualizar token push si viene del cliente
                if ($request->filled('expo_push_token')) {
                    $usuario->update(['expo_push_token' => $request->expo_push_token]);
                }

                $token = $usuario->createToken('mobile')->plainTextToken;

                return response()->json([
                    'token'   => $token,
                    'usuario' => $usuario,
                ]);
            }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->update(['expo_push_token' => null]);
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
            'facultad' => 'required|string|min:3|max:100',
        ]);

        $usuario = $request->user();

        if (!in_array($usuario->estado_repartidor, ['no_postulado', 'rechazado'])) {
            return response()->json([
                'message' => 'No puedes postular en tu estado actual.',
            ], 422);
        }

        $usuario->update([
            'facultad'          => $request->facultad,
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
