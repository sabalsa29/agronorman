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
        Schema::create('etapa_fenologica_tipo_cultivo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tipo_cultivo_id');
            //$table->foreign('tipo_cultivo_id')->references('id')->on('tipo_cultivos')->onDelete('cascade');
            $table->unsignedBigInteger('etapa_fenologica_id');
            //$table->foreign('etapa_fenologica_id')->references('id')->on('etapa_fenologicas')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etapa_fenologica_tipo_cultivo');
    }
};
