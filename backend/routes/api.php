<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\PedidoController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::get('configuraciones/publicas', function () {
    $claves = [
        'cuenta_banco', 
        'cuenta_numero', 
        'cuenta_tipo', 
        'cuenta_titular', 
        'cuenta_cedula',
        'costo_delivery',
    ];
    $configs = \App\Models\Configuracion::whereIn('clave', $claves)->get()
        ->pluck('valor', 'clave');
    return response()->json($configs);
});
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// Rutas protegidas
Route::middleware('auth:usuario')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('push-token', [AuthController::class, 'updatePushToken']);
        Route::post('postular-repartidor', [AuthController::class, 'postularRepartidor']);
    });

    // Catálogo
    Route::get('productos', [ProductoController::class, 'index']);
    Route::get('productos/{producto}', [ProductoController::class, 'show']);
    Route::get('categorias', [CategoriaController::class, 'index']);

    // Pedidos
    Route::get('pedidos', [PedidoController::class, 'index']);
    Route::post('pedidos', [PedidoController::class, 'store']);
    Route::get('pedidos/{pedido}', [PedidoController::class, 'show']);

    // Repartidor
    Route::get('repartidor/disponibles', [PedidoController::class, 'disponibles']);
    Route::get('repartidor/mis-viajes', [PedidoController::class, 'misViajes']);
    Route::post('repartidor/{pedido}/accept', [PedidoController::class, 'accept']);
    Route::post('repartidor/{pedido}/complete', [PedidoController::class, 'complete']);
    Route::get('repartidor/viaje-actual', [PedidoController::class, 'viajeActual']);
    Route::get('repartidor/mis-liquidaciones', [AuthController::class, 'misLiquidaciones']);
});