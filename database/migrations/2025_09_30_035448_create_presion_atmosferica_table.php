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
        Schema::create('presion_atmosferica', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parcela_id');
            $table->unsignedBigInteger('zona_manejo_id')->nullable();
            $table->date('fecha_solicita');
            $table->string('hora_solicita');
            $table->decimal('lat', 10, 7);
            $table->decimal('lon', 10, 7);
            $table->dateTime('fecha_hora_dato');

            // Campos específicos de presión atmosférica
            $table->decimal('pressure', 8, 2)->default(0); // Presión atmosférica en hPa
            $table->decimal('sea_level', 8, 2)->nullable(); // Presión al nivel del mar en hPa
            $table->decimal('grnd_level', 8, 2)->nullable(); // Presión al nivel del suelo en hPa

            $table->string('tipo_dato')->default('historico'); // historico, pronostico
            $table->string('fuente')->default('openweather'); // openweather, estacion
            $table->text('datos_raw')->nullable(); // Datos JSON completos de OpenWeather
            $table->timestamps();

            // Foreign keys sin índices automáticos
            //$table->foreign('parcela_id')->references('id')->on('parcelas')->onDelete('cascade');
            //$table->foreign('zona_manejo_id')->references('id')->on('zona_manejos')->onDelete('set null');

            // Índices manuales para optimizar consultas
            $table->index(['parcela_id', 'fecha_hora_dato']);
            $table->index(['tipo_dato', 'fecha_hora_dato']);
            $table->index('fecha_hora_dato');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presion_atmosferica');
    }
};
