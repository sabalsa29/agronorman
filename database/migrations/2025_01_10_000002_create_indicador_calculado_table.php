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
        Schema::create('indicador_calculado', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->unsignedBigInteger('indicador_id');
            $table->unsignedBigInteger('zona_manejo_id');
            $table->decimal('escala1', 10, 2)->default(0); // muy_bajo
            $table->decimal('escala2', 10, 2)->default(0); // bajo
            $table->decimal('escala3', 10, 2)->default(0); // optimo
            $table->decimal('escala4', 10, 2)->default(0); // alto
            $table->decimal('escala5', 10, 2)->default(0); // muy_alto
            $table->decimal('horas1', 10, 2)->default(0); // horas muy_bajo
            $table->decimal('horas2', 10, 2)->default(0); // horas bajo
            $table->decimal('horas3', 10, 2)->default(0); // horas optimo
            $table->decimal('horas4', 10, 2)->default(0); // horas alto
            $table->decimal('horas5', 10, 2)->default(0); // horas muy_alto
            $table->timestamps();

            // Relaciones
            //$table->foreign('indicador_id')->references('id')->on('indicadores')->onDelete('cascade');
            //$table->foreign('zona_manejo_id')->references('id')->on('zona_manejos')->onDelete('cascade');

            // Índices para mejorar rendimiento
            $table->index(['fecha', 'indicador_id', 'zona_manejo_id'], 'idx_ic_fecha_ind_zona');
            $table->index('fecha', 'idx_ic_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicador_calculado');
    }
};
