<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agromarket FCVT</title>
    <meta http-equiv="refresh" content="1;url={{ $deepLink }}">
    <style>
        body {
            font-family: -apple-system, system-ui, sans-serif;
            background: #f5f5f0;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
            padding: 24px;
        }
        .tarjeta { max-width: 360px; }
        h1 { font-size: 20px; color: #1a5c1a; }
        a.boton {
            display: inline-block;
            margin-top: 20px;
            padding: 14px 24px;
            background: #1a5c1a;
            color: #fff;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="tarjeta">
        <h1>{{ $titulo }}</h1>
        <p>Redirigiendo a la app automáticamente...</p>
        <a class="boton" href="{{ $deepLink }}">Volver a Agromarket</a>
    </div>
    <script>
        window.location.href = "{{ $deepLink }}";
    </script>
</body>
</html>
