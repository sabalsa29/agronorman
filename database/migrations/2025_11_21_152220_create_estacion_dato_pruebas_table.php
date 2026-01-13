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
        Schema::create('estacion_dato_pruebas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estacion_id')->nullable();
            //$table->foreign('estacion_id')->references('id')->on('estaciones');
            $table->string('id_origen', 60);
            $table->double('radiacion_solar', 5, 2)->nullable();
            $table->double('viento', 5, 2)->nullable();
            $table->double('precipitacion_acumulada', 5, 2)->nullable();
            $table->double('humedad_relativa', 5, 2)->nullable();
            $table->double('potencial_de_hidrogeno', 5, 2)->nullable();
            $table->double('conductividad_electrica', 9, 2)->nullable();
            $table->double('temperatura', 5, 2)->nullable();
            $table->double('temperatura_lvl1', 5, 2);
            $table->double('humedad_15', 5, 2)->nullable();
            $table->string('direccion_viento', 10)->nullable();
            $table->double('velocidad_viento', 5, 2)->nullable();
            $table->double('co2', 7, 2)->nullable();
            $table->double('ph', 10, 2)->nullable();
            $table->double('phos', 10, 2)->nullable();
            $table->double('nit', 10, 2)->nullable();
            $table->double('pot', 10, 2)->nullable();
            $table->double('temperatura_suelo', 5, 2)->nullable();
            $table->double('alertas')->nullable();
            $table->double('capacidad_productiva')->nullable();
            $table->float('bateria');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estacion_dato_pruebas');
    }
};
