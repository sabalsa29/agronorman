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
         Schema::create('zona_manejo_lote_icamex', function (Blueprint $table) {
            $table->id();

            // Agronorman
            $table->unsignedBigInteger('zona_manejo_id');

            //Sistema externo

            $table->string('name')->nullable();

            // Icamex (externo): guardamos el id del lote (o la clave) como referencia
            $table->unsignedBigInteger('icamex_lote_id');

            // Opcional: guardar también el station_id asociado o metadata
            // $table->unsignedBigInteger('icamex_station_id')->nullable();

            $table->timestamps();

            // Evita duplicados
            $table->unique(['zona_manejo_id', 'icamex_lote_id'], 'uq_zm_icamex_lote');

            // FK solo al lado Agronorman (local)
            $table->foreign('zona_manejo_id')
                ->references('id')
                ->on('zona_manejos')
                ->onDelete('cascade');

            // No FK hacia Icamex porque es BD externa
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zona_manejo_lote_icamex');
    }
};
