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
        Schema::create('telemetria_udp', function (Blueprint $table) {
            $table->id();

            // Información del payload
            $table->json('payload')->nullable();
            $table->string('encoding')->nullable();
            $table->string('type')->nullable();
            $table->text('value')->nullable(); // Valor base64 decodificado

            // Metadatos del mensaje
            $table->string('received')->nullable();
            $table->string('message_id')->nullable();
            $table->string('source')->nullable();
            $table->string('version')->nullable();

            // Información del dispositivo
            $table->string('iccid')->nullable();
            $table->string('ip')->nullable();
            $table->string('imsi')->nullable();

            // Datos decodificados del payload
            $table->string('estacion_id')->nullable();
            $table->string('transaccion_id')->nullable();
            $table->decimal('temp_npk_lv1', 10, 2)->nullable();
            $table->decimal('hum_npk_lv1', 10, 2)->nullable();
            $table->decimal('ph_npk_lv1', 10, 2)->nullable();
            $table->decimal('cond_npk_lv1', 10, 2)->nullable();
            $table->decimal('nit_npk_lv1', 10, 2)->nullable();
            $table->decimal('pot_npk_lv1', 10, 2)->nullable();
            $table->decimal('phos_npk_lv1', 10, 2)->nullable();
            $table->decimal('temp_sns_lv1', 10, 2)->nullable();
            $table->decimal('hum_sns_lv1', 10, 2)->nullable();
            $table->decimal('co2_sns_lv1', 10, 2)->nullable();
            $table->string('fecha')->nullable();
            $table->integer('voltaje')->nullable();
            $table->integer('contador_mnsj')->nullable();
            $table->integer('tec')->nullable();
            $table->string('ARS')->nullable();
            $table->integer('TON')->nullable();
            $table->string('CELLID')->nullable();
            $table->string('CIT')->nullable();
            $table->integer('SWV')->nullable();
            $table->string('MNC')->nullable();
            $table->string('MCC')->nullable();
            $table->string('RAT')->nullable();
            $table->string('LAC')->nullable();
            $table->string('PROJECT')->nullable();
            $table->integer('RSRP')->nullable();
            $table->integer('RSRQ')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetria_udp');
    }
};
