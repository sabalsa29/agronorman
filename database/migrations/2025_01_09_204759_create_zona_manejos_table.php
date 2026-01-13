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
        Schema::create('zona_manejos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parcela_id')->nullable();
            ////$table->foreign('parcela_id')->references('id')->on('parcelas')->onDelete('cascade');;
            $table->unsignedBigInteger('tipo_suelo_id')->nullable();
            ////$table->foreign('tipo_suelo_id')->references('id')->on('tipo_suelos');
            $table->string('nombre')->nullable();
            $table->date('fecha_inicial_uca')->nullable();
            $table->integer('temp_base_calor')->nullable();
            $table->float('edad_cultivo')->nullable(); 
            $table->date('fecha_siembra')->nullable();
            $table->integer('objetivo_produccion')->nullable();
            $table->boolean('status')->default(true);
            $table->integer('cultivo_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estacion_virtuals');
    }
};
