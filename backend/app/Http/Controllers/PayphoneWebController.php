<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\PayphoneConfirmacionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Estas dos páginas existen SOLO porque Payphone exige que la navegación
// hacia su formulario de pago (y la respuesta de vuelta) ocurra dentro de
// un dominio web real registrado en su portal — no acepta abrir el enlace
// de pago directo desde una app móvil (bloquea con "NO AUTORIZADO" por
// falta de un Referer válido). Estas vistas son el puente entre la app y
// Payphone: cargan brevemente en el navegador del celular y rebotan.
class PayphoneWebController extends Controller
{
    // La app abre esta URL. Simplemente redirige al formulario de pago que
    // ya se preparó en PayphoneController::prepare(), pero como la
    // navegación SALE de nuestro dominio, Payphone sí la acepta.
    public function bridge(string $clientTransactionId)
    {
        $pedido = Pedido::where('payphone_client_transaction_id', $clientTransactionId)->first();

        if (!$pedido || !$pedido->payphone_pay_url) {
            return response()->view('payphone.error', [
                'mensaje' => 'No se encontró el pedido o el enlace de pago expiró.',
            ], 404);
        }

        if ($pedido->estado !== 'pendiente_pago') {
            return response()->view('payphone.error', [
                'mensaje' => 'Este pedido ya fue procesado.',
            ]);
        }

        return redirect()->away($pedido->payphone_pay_url);
    }

    // Alternativa a bridge(): en vez de redirigir al formulario hospedado
    // por Payphone, embebe el widget "Cajita de Pagos" directo en esta
    // página (el usuario paga sin salir de esta pantalla).
    public function cajita(string $clientTransactionId)
    {
        $pedido = Pedido::where('payphone_client_transaction_id', $clientTransactionId)->first();

        if (!$pedido) {
            return response()->view('payphone.error', [
                'mensaje' => 'No se encontró el pedido o el enlace de pago expiró.',
            ], 404);
        }

        if ($pedido->estado !== 'pendiente_pago') {
            return response()->view('payphone.error', [
                'mensaje' => 'Este pedido ya fue procesado.',
            ]);
        }

        return view('payphone.cajita', [
            'pedido' => $pedido,
            'montoCentavos' => (int) round($pedido->total * 100),
            'token' => config('services.payphone.token'),
            'storeId' => config('services.payphone.store_id'),
        ]);
    }

    // Payphone redirige aquí después del pago, con "id" y "clientTransactionId"
    // en la URL. Confirmamos server-to-server y rebotamos a la app vía deep link.
    public function confirmar(Request $request): View
    {
        $id = (int) $request->query('id');
        $clientTransactionId = (string) $request->query('clientTransactionId');

        if (!$id || !$clientTransactionId) {
            return view('payphone.redirigiendo', [
                'deepLink' => 'agromarket://payphone-redirect?resultado=error',
                'titulo' => 'Faltan datos de la transacción',
            ]);
        }

        $resultado = PayphoneConfirmacionService::confirmar($clientTransactionId, $id);
        $pedidoId = $resultado['pedido']?->id;

        [$titulo, $ok] = match ($resultado['resultado']) {
            PayphoneConfirmacionService::OK => ['¡Pago confirmado! Ya puedes volver a la app.', true],
            PayphoneConfirmacionService::YA_PROCESADO => ['Este pedido ya había sido procesado.', true],
            PayphoneConfirmacionService::SIN_STOCK => ['Pago recibido, pero ya no hay stock. Un administrador te contactará.', true],
            default => ['El pago no fue aprobado.', false],
        };

        $deepLink = 'agromarket://payphone-redirect?'.http_build_query([
            'resultado' => $ok ? 'exito' : 'error',
            'pedido_id' => $pedidoId,
        ]);

        return view('payphone.redirigiendo', compact('deepLink', 'titulo'));
    }
}
