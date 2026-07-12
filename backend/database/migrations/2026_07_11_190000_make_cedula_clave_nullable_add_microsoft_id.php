<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('cedula', 15)->nullable()->change();
            $table->string('clave')->nullable()->change();
            $table->string('microsoft_id')->nullable()->unique()->after('correo');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('microsoft_id');
            $table->string('cedula', 15)->nullable(false)->change();
            $table->string('clave')->nullable(false)->change();
        });
    }
};
