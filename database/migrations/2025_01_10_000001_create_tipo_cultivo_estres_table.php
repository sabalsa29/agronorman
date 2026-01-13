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
        Schema::create('tipo_cultivo_estres', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tipo_cultivo_id');
            $table->unsignedBigInteger('variable_id');
            $table->enum('tipo', ['DIURNO', 'NOCTURNO']);
            $table->decimal('muy_bajo', 10, 2)->nullable();
            $table->decimal('bajo_min', 10, 2)->nullable();
            $table->decimal('bajo_max', 10, 2)->nullable();
            $table->decimal('optimo_min', 10, 2)->nullable();
            $table->decimal('optimo_max', 10, 2)->nullable();
            $table->decimal('alto_min', 10, 2)->nullable();
            $table->decimal('alto_max', 10, 2)->nullable();
            $table->decimal('muy_alto', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Relaciones
            ////$table->foreign('tipo_cultivo_id')->references('id')->on('tipo_cultivos')->onDelete('cascade');
            //$table->foreign('variable_id')->references('id')->on('variables_medicion')->onDelete('cascade');

            // Índices para mejorar rendimiento
            $table->index(['tipo_cultivo_id', 'variable_id', 'tipo'], 'idx_tce_tc_var_tipo');
            $table->index('variable_id', 'idx_tce_variable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_cultivo_estres');
    }
};
