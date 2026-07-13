<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/archivos/{path}', function (string $path) {
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }

    $contenido = Storage::disk('public')->get($path);

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
    ];
    $mime = $mimes[$extension] ?? 'application/octet-stream';

    return response($contenido, 200)->header('Content-Type', $mime);
})->where('path', '.*');

// Payphone exige que la navegación hacia su formulario (y la respuesta de
// vuelta) ocurra en un dominio web real registrado en su portal — no acepta
// abrir el enlace de pago directo desde una app móvil. Estas dos rutas son
// el puente entre la app y Payphone. Ver PayphoneWebController para el
// porqué completo.
use App\Http\Controllers\PayphoneWebController;

Route::get('/payphone/bridge/{clientTransactionId}', [PayphoneWebController::class, 'bridge']);
Route::get('/payphone/cajita/{clientTransactionId}', [PayphoneWebController::class, 'cajita']);
Route::get('/payphone/confirmar', [PayphoneWebController::class, 'confirmar']);
