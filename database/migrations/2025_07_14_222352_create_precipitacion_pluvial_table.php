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
        Schema::create('precipitacion_pluvial', function (Blueprint $table) {
            $table->id();
            //$table->foreignId('parcela_id')->constrained('parcelas');
            //$table->foreignId('zona_manejo_id')->nullable()->constrained('zona_manejos');
            $table->date('fecha_solicita');
            $table->string('hora_solicita');
            $table->decimal('lat', 10, 7);
            $table->decimal('lon', 10, 7);
            $table->dateTime('fecha_hora_dato');
            $table->decimal('precipitacion_mm', 10, 2)->default(0);
            $table->decimal('precipitacion_probabilidad', 10, 2)->default(0);
            $table->string('tipo_dato')->default('historico'); // historico, pronostico
            $table->string('fuente')->default('openweather'); // openweather, estacion
            $table->text('datos_raw')->nullable(); // Datos JSON completos de OpenWeather
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precipitacion_pluvial');
    }
};
