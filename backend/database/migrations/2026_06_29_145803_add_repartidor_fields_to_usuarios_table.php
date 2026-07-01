<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('es_repartidor');

            $table->enum('estado_repartidor', [
                'no_postulado',
                'pendiente',
                'aprobado',
                'rechazado',
            ])->default('no_postulado')->after('rol');

            $table->string('facultad', 100)->nullable()->after('estado_repartidor');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['estado_repartidor', 'facultad']);

            $table->boolean('es_repartidor')->default(false);
        });
    }
};
