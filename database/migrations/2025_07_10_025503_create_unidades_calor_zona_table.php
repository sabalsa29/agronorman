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
        Schema::create('unidades_calor_zona', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zona_manejo_id');
            $table->date('fecha');
            $table->double('unidades');
            $table->timestamps();
            $table->softDeletes();

            // Relación con zona_manejos
            //$table->foreign('zona_manejo_id')->references('id')->on('zona_manejos')->onDelete('cascade');

            // Índice compuesto para consultas eficientes
            $table->index(['zona_manejo_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_calor_zona');
    }
};
