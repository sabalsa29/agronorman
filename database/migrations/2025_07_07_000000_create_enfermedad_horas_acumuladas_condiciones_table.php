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
        Schema::create('enfermedad_horas_acumuladas_condiciones', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha');
            $table->integer('minutos');
            $table->unsignedBigInteger('tipo_cultivo_id');
            $table->unsignedBigInteger('enfermedad_id');
            $table->unsignedBigInteger('estacion_id');
            $table->timestamps();

            // Relaciones (claves foráneas)
            //$table->foreign('tipo_cultivo_id')->references('id')->on('tipo_cultivos')->onDelete('cascade');
            //$table->foreign('enfermedad_id')->references('id')->on('enfermedades')->onDelete('cascade');
            //$table->foreign('estacion_id')->references('id')->on('estaciones')->onDelete('cascade');

            // Índices para mejorar el rendimiento de consultas (nombres personalizados para evitar límite de MySQL)
            $table->index(['fecha', 'tipo_cultivo_id', 'enfermedad_id'], 'idx_ehac_fecha_tc_enf');
            $table->index(['estacion_id', 'fecha'], 'idx_ehac_est_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enfermedad_horas_acumuladas_condiciones');
    }
};
