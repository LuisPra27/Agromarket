<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PayphoneService
{
    private const BASE_URL = 'https://pay.payphonetodoesposible.com/api/button';

    // Payphone requiere el token de la app de comercio configurado en su portal.
    // Ver: config/services.php -> 'payphone' -> 'token'
    private static function token(): string
    {
        return (string) config('services.payphone.token');
    }

    // Prepara una transacción antes de mandar al cliente a pagar. Devuelve
    // la URL a la que hay que redirigir (payWithCard) para que complete el pago.
    // El monto se manda en CENTAVOS (entero), como exige la API de Payphone.
    public static function prepare(
        string $clientTransactionId,
        float $monto,
        string $referencia
    ): array {
        $montoCentavos = (int) round($monto * 100);

        $response = Http::withToken(self::token())
            ->post(self::BASE_URL.'/Prepare', [
                'amount' => $montoCentavos,
                'amountWithoutTax' => $montoCentavos,
                'amountWithTax' => 0,
                'tax' => 0,
                'service' => 0,
                'tip' => 0,
                'currency' => 'USD',
                'reference' => $referencia,
                'clientTransactionId' => $clientTransactionId,
                // Deep link de la app (mismo esquema que usamos para el login
                // de Microsoft); Payphone redirige acá al terminar el pago.
                'responseUrl' => 'agromarket://payphone-redirect',
                'storeId' => config('services.payphone.store_id'),
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('No se pudo preparar el pago con Payphone: '.$response->body());
        }

        return $response->json();
    }

    // Confirma (server-to-server) que una transacción realmente se pagó.
    // NUNCA hay que confiar solo en el redirect del navegador del cliente;
    // esta llamada es la que de verdad valida el pago contra Payphone.
    public static function confirm(int $id, string $clientTransactionId): array
    {
        $response = Http::withToken(self::token())
            ->post(self::BASE_URL.'/V2/Confirm', [
                'id' => $id,
                'clientTxId' => $clientTransactionId,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('No se pudo confirmar el pago con Payphone: '.$response->body());
        }

        return $response->json();
    }
}
