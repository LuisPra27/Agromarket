<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushService
{
    // Envía una notificación a uno o varios expo_push_token
    public static function enviar(array $tokens, string $titulo, string $cuerpo, array $data = []): void
    {
        // Filtramos tokens vacíos/null (ej. usuario nunca abrió la app)
        $tokens = array_values(array_filter($tokens));

        if (empty($tokens)) {
            return;
        }

        $mensajes = array_map(fn ($token) => [
            'to' => $token,
            'sound' => 'default',
            'title' => $titulo,
            'body' => $cuerpo,
            'data' => $data,
        ], $tokens);

        try {
            $response = Http::post('https://exp.host/--/api/v2/push/send', $mensajes);

            // TEMPORAL: log de la respuesta completa para depurar
            Log::info('Expo push respuesta', [
                'tokens' => $tokens,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                Log::warning('Expo push falló', ['response' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::error('Error enviando Expo push: ' . $e->getMessage());
        }
    }
}
