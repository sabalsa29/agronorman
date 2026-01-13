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
        Schema::create('unidades_calor', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha');
            $table->integer('unidades_calor');
            $table->unsignedBigInteger('plaga_id');
            $table->unsignedBigInteger('tipo_cultivo_id');
            $table->unsignedBigInteger('parcela_id');
            $table->timestamps();

            // Relaciones (claves foráneas)
            //$table->foreign('plaga_id')->references('id')->on('plagas')->onDelete('cascade');
            //$table->foreign('tipo_cultivo_id')->references('id')->on('tipo_cultivos')->onDelete('cascade');
            //$table->foreign('parcela_id')->references('id')->on('parcelas')->onDelete('cascade');

            // Índices para mejorar el rendimiento
            $table->index(['fecha', 'plaga_id'], 'idx_uc_fecha_plaga');
            $table->index(['parcela_id', 'fecha'], 'idx_uc_parc_fecha');
            $table->index(['tipo_cultivo_id', 'fecha'], 'idx_uc_tc_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_calor');
    }
};
