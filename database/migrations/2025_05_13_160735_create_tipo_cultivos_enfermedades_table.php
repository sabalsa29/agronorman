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
        Schema::create('tipo_cultivos_enfermedades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tipo_cultivo_id');
            //$table->foreign('tipo_cultivo_id')->references('id')->on('tipo_cultivos')->onDelete('cascade');
            $table->unsignedBigInteger('enfermedad_id');
            //$table->foreign('enfermedad_id')->references('id')->on('enfermedades')->onDelete('cascade');
            $table->integer('riesgo_humedad')->nullable();
            $table->integer('riesgo_humedad_max')->nullable();
            $table->integer('riesgo_temperatura')->nullable();
            $table->integer('riesgo_temperatura_max')->nullable();
            $table->integer('riesgo_medio')->nullable();
            $table->integer('riesgo_mediciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_cultivos_enfermedades');
    }
};
