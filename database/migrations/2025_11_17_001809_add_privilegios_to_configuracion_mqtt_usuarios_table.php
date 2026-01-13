<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('configuracion_mqtt_usuarios', function (Blueprint $table) {
            $table->json('estaciones_permitidas')->nullable()->after('activo');
            // JSON: array de IDs de estaciones [1, 2, 3] o null para todas
            $table->json('parametros_permitidos')->nullable()->after('estaciones_permitidas');
            // JSON: {"PCF": true, "PCR": true, "PTP": false, ...} o null para todos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_mqtt_usuarios', function (Blueprint $table) {
            $table->dropColumn(['estaciones_permitidas', 'parametros_permitidos']);
        });
    }
};
