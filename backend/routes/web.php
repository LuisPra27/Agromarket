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

Route::get('/test-ruta', function () {
    return 'funciona';
});
