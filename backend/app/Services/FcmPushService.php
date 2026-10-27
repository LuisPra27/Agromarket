<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Sustituye a ExpoPushService. El cliente movil nativo recibe por FCM, y la
// API de Expo (exp.host) solo acepta tokens con formato ExponentPushToken[...]
// asi que un token de FCM lo rechazaba en silencio.
//
// Se habla con FCM HTTP v1 a mano, sin kreait/firebase-php ni google/apiclient:
// son ~80 lineas, no añade dependencias a la imagen y el flujo OAuth
// server-to-server queda a la vista.
class FcmPushService
{
    private const ALCANCE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const URL_TOKEN = 'https://oauth2.googleapis.com/token';

    private const CLAVE_CACHE = 'fcm_access_token';

    // Misma firma que el servicio viejo, para no tocar los sitios que llaman.
    public static function enviar(array $tokens, string $titulo, string $cuerpo, array $data = []): void
    {
        $tokens = array_values(array_unique(array_filter($tokens)));

        if (empty($tokens)) {
            return;
        }

        $credenciales = self::credenciales();
        if (! $credenciales) {
            return;
        }

        $accessToken = self::accessToken($credenciales);
        if (! $accessToken) {
            return;
        }

        // HTTP v1 no manda a varios tokens en una sola llamada (eso era la API
        // legacy). Los SDK de admin tambien hacen un bucle por dentro.
        foreach ($tokens as $token) {
            self::enviarA($credenciales['project_id'], $accessToken, $token, $titulo, $cuerpo, $data);
        }
    }

    private static function enviarA(
        string $projectId,
        string $accessToken,
        string $token,
        string $titulo,
        string $cuerpo,
        array $data
    ): void {
        $mensaje = [
            'message' => [
                'token' => $token,
                // Con "notification" presente, el SDK de Android dibuja el
                // aviso cuando la app no esta en primer plano; con la app
                // abierta llega a onMessageReceived.
                'notification' => [
                    'title' => $titulo,
                    'body' => $cuerpo,
                ],
                // TODOS los valores de data tienen que ser string o FCM
                // responde 400 INVALID_ARGUMENT. Ojo con pedido_id, que es int
                // en PHP. El cliente los lee con getStringExtra.
                'data' => array_map(static fn ($valor) => (string) $valor, $data),
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        // Tiene que coincidir con el canal que crea la app en
                        // AgromarketApp.onCreate (R.string.canal_pedidos_id).
                        'channel_id' => 'pedidos',
                        'sound' => 'default',
                    ],
                ],
            ],
        ];

        try {
            $respuesta = Http::withToken($accessToken)
                ->timeout(10)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $mensaje);

            if ($respuesta->successful()) {
                return;
            }

            $motivo = data_get($respuesta->json(), 'error.details.0.errorCode')
                ?? data_get($respuesta->json(), 'error.status');

            // El usuario desinstalo la app o el token caduco. Borrarlo evita
            // reintentar para siempre contra un dispositivo que ya no existe.
            if (in_array($motivo, ['UNREGISTERED', 'NOT_FOUND'], true)) {
                Usuario::where('expo_push_token', $token)->update(['expo_push_token' => null]);
                Log::info('FCM: token muerto, se borra de usuarios');

                return;
            }

            // INVALID_ARGUMENT NO borra el token: casi siempre es un payload
            // mal construido por nosotros, y borrarlo taparia el bug.
            Log::warning('FCM fallo al enviar', [
                'status' => $respuesta->status(),
                'body' => $respuesta->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('FCM excepcion al enviar: '.$e->getMessage());
        }
    }

    private static function credenciales(): ?array
    {
        $base64 = config('services.fcm.credentials');

        if (blank($base64)) {
            Log::warning('FCM sin credenciales: falta FIREBASE_CREDENTIALS_BASE64 en .env');

            return null;
        }

        $json = base64_decode($base64, true);
        $datos = $json ? json_decode($json, true) : null;

        if (! is_array($datos) || ! isset($datos['project_id'], $datos['client_email'], $datos['private_key'])) {
            Log::error('FCM: FIREBASE_CREDENTIALS_BASE64 no es un JSON de cuenta de servicio valido');

            return null;
        }

        return $datos;
    }

    // Google devuelve un access token de 1 hora. Se cachea para no firmar un
    // JWT y pedir token en cada notificacion.
    private static function accessToken(array $credenciales): ?string
    {
        $cacheado = Cache::get(self::CLAVE_CACHE);
        if ($cacheado) {
            return $cacheado;
        }

        $jwt = self::firmarJwt($credenciales);
        if (! $jwt) {
            return null;
        }

        try {
            $respuesta = Http::asForm()->timeout(10)->post(self::URL_TOKEN, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);
        } catch (\Throwable $e) {
            Log::error('FCM: no se pudo pedir el access token: '.$e->getMessage());

            return null;
        }

        if ($respuesta->failed()) {
            Log::error('FCM: Google rechazo el JWT', ['body' => $respuesta->body()]);

            return null;
        }

        $token = $respuesta->json('access_token');
        if (! $token) {
            return null;
        }

        // Un minuto de colchon por si la siguiente peticion cae justo en el
        // limite. A proposito NO se usa Cache::remember: ahi un fallo que
        // devuelve null quedaria cacheado y la app se quedaria muda una hora.
        $segundos = (int) ($respuesta->json('expires_in') ?: 3600);
        Cache::put(self::CLAVE_CACHE, $token, now()->addSeconds(max($segundos - 60, 60)));

        return $token;
    }

    // JWT firmado con la clave privada de la cuenta de servicio: es lo que
    // Google cambia por un access token (flujo "JWT bearer", sin navegador ni
    // usuario, porque aqui el que se autentica es el servidor).
    private static function firmarJwt(array $credenciales): ?string
    {
        $ahora = time();

        $cabecera = ['alg' => 'RS256', 'typ' => 'JWT'];
        $cuerpo = [
            'iss' => $credenciales['client_email'],
            'scope' => self::ALCANCE,
            'aud' => self::URL_TOKEN,
            'iat' => $ahora,
            'exp' => $ahora + 3600,
        ];

        $sinFirma = self::base64Url(json_encode($cabecera)).'.'.self::base64Url(json_encode($cuerpo));

        $clave = openssl_pkey_get_private($credenciales['private_key']);
        $firma = '';

        if (! $clave || ! openssl_sign($sinFirma, $firma, $clave, OPENSSL_ALGO_SHA256)) {
            Log::error('FCM: no se pudo firmar el JWT con la clave privada');

            return null;
        }

        return $sinFirma.'.'.self::base64Url($firma);
    }

    // base64 "url safe": sin + ni / ni relleno de =. Lo exige el formato JWT.
    private static function base64Url(string $datos): string
    {
        return rtrim(strtr(base64_encode($datos), '+/', '-_'), '=');
    }
}
