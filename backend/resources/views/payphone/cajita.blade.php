<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agromarket FCVT — Pago</title>
    <link rel="stylesheet" href="https://cdn.payphonetodoesposible.com/box/v2.0/payphone-payment-box.css">
    <script type="module" src="https://cdn.payphonetodoesposible.com/box/v2.0/payphone-payment-box.js"></script>
    <style>
        body {
            font-family: -apple-system, system-ui, sans-serif;
            background: #f5f5f0;
            color: #111827;
            margin: 0;
            padding: 24px 16px;
            text-align: center;
        }
        h1 { font-size: 18px; color: #1a5c1a; margin-bottom: 4px; }
        p.monto { font-size: 15px; color: #6b7280; margin-bottom: 24px; }
        #pp-button { display: flex; justify-content: center; }
    </style>
</head>
<body>
    <h1>Agromarket FCVT</h1>
    <p class="monto">Total a pagar: ${{ number_format($pedido->total, 2) }}</p>

    <div id="pp-button"></div>

    <script>
        // A diferencia del Botón de Pago, la Cajita NO acepta "responseUrl"
        // por parámetro — usa la "URL de Respuesta" que configuraste en el
        // portal de Payphone Developer para esta app (debe ser
        // {{ url('/payphone/confirmar') }}).
        window.addEventListener('DOMContentLoaded', () => {
            new PPaymentButtonBox({
                token: '{{ $token }}',
                clientTransactionId: '{{ $pedido->payphone_client_transaction_id }}',
                amount: {{ $montoCentavos }},
                amountWithoutTax: {{ $montoCentavos }},
                currency: 'USD',
                storeId: '{{ $storeId }}',
                reference: 'Pedido Agromarket #{{ $pedido->id }}',
                lang: 'es',
            }).render('pp-button');
        });
    </script>
</body>
</html>
