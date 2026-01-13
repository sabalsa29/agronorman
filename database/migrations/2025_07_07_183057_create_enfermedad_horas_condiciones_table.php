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
        Schema::create('enfermedad_horas_condiciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enfermedad_id');
            $table->unsignedBigInteger('tipo_cultivo_id');
            $table->unsignedBigInteger('estacion_id');
            $table->dateTime('fecha_ultima_transmision');
            $table->integer('minutos')->default(0);
            $table->timestamps();

            // Relaciones (claves foráneas)
            //$table->foreign('enfermedad_id')->references('id')->on('enfermedades')->onDelete('cascade');
            //$table->foreign('tipo_cultivo_id')->references('id')->on('tipo_cultivos')->onDelete('cascade');
            //$table->foreign('estacion_id')->references('id')->on('estaciones')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enfermedad_horas_condiciones');
    }
};
