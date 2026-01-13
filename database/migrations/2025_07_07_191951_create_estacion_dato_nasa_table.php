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
        Schema::create('estacion_dato_nasa', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estacion_id');
            $table->unsignedBigInteger('parcela_id');
            $table->date('created_at');
            $table->date('updated_at');

            // Datos de NASA
            $table->decimal('insolacion', 10, 4)->nullable();
            $table->decimal('precipitacion', 10, 4)->nullable();
            $table->decimal('presion', 10, 4)->nullable();
            $table->decimal('humedad_relativa_2_metros', 10, 4)->nullable();
            $table->decimal('temperatura_2_metros', 10, 4)->nullable();
            $table->decimal('punto_rocio', 10, 4)->nullable();
            $table->decimal('temperatura_bulbo_2_metros', 10, 4)->nullable();
            $table->decimal('temperatura_maxima_2_metros', 10, 4)->nullable();
            $table->decimal('temperatura_minima_2_metros', 10, 4)->nullable();
            $table->decimal('rango_temperatura_2_metros', 10, 4)->nullable();
            $table->decimal('velocidad_viento_2_metros', 10, 4)->nullable();
            $table->decimal('temperatura_superficie', 10, 4)->nullable();
            $table->decimal('velocidad_viento_10_metros', 10, 4)->nullable();
            $table->decimal('velocidad_maxima_viento_10_metros', 10, 4)->nullable();
            $table->decimal('velocidad_minima_viento_10_metros', 10, 4)->nullable();
            $table->decimal('velocidad_maxima_viento_2_metros', 10, 4)->nullable();
            $table->decimal('velocidad_minima_viento_2_metros', 10, 4)->nullable();
            $table->decimal('velocidad_viento_50_metros', 10, 4)->nullable();

            // Relaciones (claves foráneas)
            //$table->foreign('estacion_id')->references('id')->on('estaciones')->onDelete('cascade');
            //$table->foreign('parcela_id')->references('id')->on('parcelas')->onDelete('cascade');

            // Índices para mejorar el rendimiento
            $table->index(['estacion_id', 'created_at'], 'idx_edn_est_fecha');
            $table->index(['parcela_id', 'created_at'], 'idx_edn_parc_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estacion_dato_nasa');
    }
};
