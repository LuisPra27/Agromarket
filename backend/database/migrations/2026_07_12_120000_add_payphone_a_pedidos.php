<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('metodo_pago')->default('transferencia')->after('metodo_entrega');
            $table->string('payphone_client_transaction_id')->nullable()->unique()->after('comprobante_pago_url');
            $table->string('payphone_transaction_id')->nullable()->after('payphone_client_transaction_id');
        });

        // Igual que en la migración anterior de "estado": Postgres exige
        // soltar el check constraint y recrearlo con la lista completa.
        DB::statement('ALTER TABLE pedidos DROP CONSTRAINT pedidos_estado_check');
        DB::statement("ALTER TABLE pedidos ADD CONSTRAINT pedidos_estado_check CHECK (estado IN (
            'pendiente_pago',
            'pendiente_validacion',
            'rechazado',
            'preparando',
            'listo_para_delivery',
            'en_camino',
            'entregado',
            'cancelado'
        ))");
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['metodo_pago', 'payphone_client_transaction_id', 'payphone_transaction_id']);
        });

        DB::statement('ALTER TABLE pedidos DROP CONSTRAINT pedidos_estado_check');
        DB::statement("ALTER TABLE pedidos ADD CONSTRAINT pedidos_estado_check CHECK (estado IN (
            'pendiente_validacion',
            'rechazado',
            'preparando',
            'listo_para_delivery',
            'en_camino',
            'entregado',
            'cancelado'
        ))");
    }
};
