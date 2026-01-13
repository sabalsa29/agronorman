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
        Schema::create('alerta_enfermedad', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->unsignedBigInteger('enfermedad_id');
            $table->unsignedBigInteger('estacion_id');
            $table->unsignedBigInteger('parcela_id');
            $table->integer('horas');
            $table->timestamps();

            // Relaciones (claves foráneas)
            //$table->foreign('enfermedad_id')->references('id')->on('enfermedades')->onDelete('cascade');
            //$table->foreign('estacion_id')->references('id')->on('estaciones')->onDelete('cascade');
            //$table->foreign('parcela_id')->references('id')->on('parcelas')->onDelete('cascade');

            // Índices para mejorar el rendimiento
            $table->index(['fecha', 'enfermedad_id'], 'idx_ae_fecha_enf');
            $table->index(['parcela_id', 'fecha'], 'idx_ae_parc_fecha');
            $table->index(['estacion_id', 'fecha'], 'idx_ae_est_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerta_enfermedad');
    }
};
