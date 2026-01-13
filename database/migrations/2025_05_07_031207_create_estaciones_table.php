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
        Schema::create('estaciones', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 60);
            $table->unsignedBigInteger('tipo_estacion_id')->nullable();
            //$table->foreign('tipo_estacion_id')->references('id')->on('tipo_estacions');
            $table->unsignedBigInteger('cliente_id')->nullable();
            //$table->foreign('cliente_id')->references('id')->on('clientes');
            $table->unsignedBigInteger('fabricante_id')->nullable();
            //$table->foreign('fabricante_id')->references('id')->on('fabricantes');
            $table->unsignedBigInteger('almacen_id')->nullable();
            //$table->foreign('almacen_id')->references('id')->on('almacens');
            $table->string('celular', 20)->nullable();
            $table->string('caracteristicas', 255)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estaciones');
    }
};
