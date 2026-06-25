<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')
                ->constrained('usuarios')
                ->onDelete('restrict');
            $table->foreignId('repartidor_id')
                ->nullable()
                ->constrained('usuarios')
                ->onDelete('set null');
            $table->decimal('total', 10, 2);
            $table->enum('metodo_entrega', ['retiro', 'delivery']);
            $table->enum('estado', [
                'pendiente_validacion',
                'rechazado',
                'preparando',
                'listo_para_delivery',
                'en_camino',
                'entregado',
            ])->default('pendiente_validacion');
            $table->string('comprobante_pago_url')->nullable();
            $table->string('codigo_qr_hash')->nullable()->unique();
            $table->text('punto_encuentro')->nullable();
            $table->float('pin_x')->nullable();
            $table->float('pin_y')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
