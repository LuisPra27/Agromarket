<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Postgres no permite editar un enum de tipo `check constraint` con
        // ALTER COLUMN directamente; hay que soltar el constraint y volver a
        // crearlo con la lista completa de valores (los de antes + el nuevo).
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

        Schema::table('pedidos', function (Blueprint $table) {
            $table->text('motivo_cancelacion')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('motivo_cancelacion');
        });

        DB::statement('ALTER TABLE pedidos DROP CONSTRAINT pedidos_estado_check');
        DB::statement("ALTER TABLE pedidos ADD CONSTRAINT pedidos_estado_check CHECK (estado IN (
            'pendiente_validacion',
            'rechazado',
            'preparando',
            'listo_para_delivery',
            'en_camino',
            'entregado'
        ))");
    }
};
