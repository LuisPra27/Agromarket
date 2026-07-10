<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedInteger('numero_orden_cliente')->nullable()->after('id');
        });

        // Poblar pedidos existentes: numeración por cliente según created_at
        $pedidos = DB::table('pedidos')
            ->orderBy('cliente_id')
            ->orderBy('created_at')
            ->get(['id', 'cliente_id']);

        $contadorPorCliente = [];
        foreach ($pedidos as $pedido) {
            $contadorPorCliente[$pedido->cliente_id] = ($contadorPorCliente[$pedido->cliente_id] ?? 0) + 1;
            DB::table('pedidos')
                ->where('id', $pedido->id)
                ->update(['numero_orden_cliente' => $contadorPorCliente[$pedido->cliente_id]]);
        }

        // Hacer la columna not null tras poblar
        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedInteger('numero_orden_cliente')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('numero_orden_cliente');
        });
    }
};