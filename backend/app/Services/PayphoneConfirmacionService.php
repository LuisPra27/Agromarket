<?php

namespace App\Services;

use App\Models\Pedido;

class PayphoneConfirmacionService
{
    // Resultados posibles, para que quien llame decida qué mostrar/responder.
    public const OK = 'ok';
    public const YA_PROCESADO = 'ya_procesado';
    public const RECHAZADO = 'rechazado';
    public const SIN_STOCK = 'sin_stock';
    public const NO_ENCONTRADO = 'no_encontrado';

    // Confirma un pago de Payphone contra su API (server-to-server) y, si fue
    // aprobado, reutiliza PedidoAprobacionService para reducir stock y generar
    // el QR — el mismo flujo que la aprobación manual en Filament.
    public static function confirmar(string $clientTransactionId, int $id): array
    {
        // La Cajita usa "{uuid}-cajita" para no chocar con el clientTransactionId
        // que el Botón de Pago ya reservó al llamar a Prepare (ver cajita.blade.php).
        // Para buscar el pedido usamos el UUID base; para confirmar con Payphone
        // usamos el clientTransactionId tal cual, porque es el que Payphone
        // tiene registrado para esa transacción específica.
        $idBaseDelPedido = str_ends_with($clientTransactionId, '-cajita')
            ? substr($clientTransactionId, 0, -strlen('-cajita'))
            : $clientTransactionId;

        $pedido = Pedido::where('payphone_client_transaction_id', $idBaseDelPedido)->first();

        if (!$pedido) {
            return ['resultado' => self::NO_ENCONTRADO, 'pedido' => null];
        }

        if ($pedido->estado !== 'pendiente_pago') {
            return ['resultado' => self::YA_PROCESADO, 'pedido' => $pedido];
        }

        $respuesta = PayphoneService::confirm($id, $clientTransactionId);

        if (($respuesta['transactionStatus'] ?? null) !== 'Approved') {
            $pedido->update(['estado' => 'rechazado']);
            return ['resultado' => self::RECHAZADO, 'pedido' => $pedido];
        }

        $pedido->update(['payphone_transaction_id' => (string) $id]);

        try {
            PedidoAprobacionService::aprobar($pedido);
        } catch (\Throwable $e) {
            // El pago sí se cobró pero ya no hay stock (caso raro: se agotó
            // entre el prepare y el confirm). Queda para revisión manual.
            $pedido->update(['estado' => 'pendiente_validacion']);
            return ['resultado' => self::SIN_STOCK, 'pedido' => $pedido->fresh()];
        }

        return ['resultado' => self::OK, 'pedido' => $pedido->fresh()->load('detalles.producto')];
    }
}
