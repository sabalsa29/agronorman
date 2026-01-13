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
        Schema::create('tipo_cultivo_variable', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tipo_cultivo_id');
            $table->unsignedBigInteger('variable_id');
            $table->string('imagen_grafico')->nullable();
            $table->string('imagen_colorimetria')->nullable();
            $table->timestamps();

            // Relaciones (claves foráneas)
            //$table->foreign('tipo_cultivo_id')->references('id')->on('tipo_cultivos')->onDelete('cascade');
            //$table->foreign('variable_id')->references('id')->on('variables_medicion')->onDelete('cascade');

            // Índices para mejorar el rendimiento
            $table->index(['tipo_cultivo_id', 'variable_id'], 'idx_tcv_tc_var');
            $table->index('variable_id', 'idx_tcv_variable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_cultivo_variable');
    }
};
