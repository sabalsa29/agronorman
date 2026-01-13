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
        Schema::create('datos_viento', function (Blueprint $table) {
            $table->id();
            //$table->foreignId('parcela_id')->constrained('parcelas');
            //$table->foreignId('zona_manejo_id')->nullable()->constrained('zona_manejos');
            $table->date('fecha_solicita');
            $table->string('hora_solicita');
            $table->decimal('lat', 10, 7);
            $table->decimal('lon', 10, 7);
            $table->dateTime('fecha_hora_dato');

            // Datos de viento de OpenWeather
            $table->decimal('wind_speed', 8, 2)->default(0); // Velocidad del viento (m/s)
            $table->decimal('wind_gust', 8, 2)->nullable(); // Ráfagas de viento (m/s)
            $table->integer('wind_deg')->nullable(); // Dirección del viento (grados)
            $table->string('wind_direction')->nullable(); // Dirección cardinal (N, NE, E, etc.)

            // Datos adicionales de viento disponibles en OpenWeather
            $table->decimal('wind_speed_2m', 8, 2)->nullable(); // Velocidad viento a 2m (m/s)
            $table->decimal('wind_speed_10m', 8, 2)->nullable(); // Velocidad viento a 10m (m/s)
            $table->decimal('wind_gust_10m', 8, 2)->nullable(); // Ráfagas a 10m (m/s)

            // Metadatos
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
        Schema::dropIfExists('datos_viento');
    }
};
