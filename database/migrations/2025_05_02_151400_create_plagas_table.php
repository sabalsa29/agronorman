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
        Schema::create('plagas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug', 60);
            $table->string('imagen');
            $table->text('descripcion')->nullable();
            $table->float('posicion1', 10, 0)->nullable();
            $table->float('posicion2', 10, 0)->nullable();
            $table->float('posicion3', 10, 0)->nullable();
            $table->float('posicion4', 10, 0)->nullable();
            $table->float('posicion5', 10, 0)->nullable();
            $table->float('posicion6', 10, 0)->nullable();
            $table->float('umbral_min', 10, 0)->nullable();
            $table->float('umbral_max', 10, 0)->nullable();
            $table->float('alarmas_acumuladas', 10, 0)->nullable();
            $table->integer('unidades_calor_ciclo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plagas');
    }
};
