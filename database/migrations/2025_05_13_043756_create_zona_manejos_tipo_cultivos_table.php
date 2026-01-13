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
        Schema::create('zona_manejos_tipo_cultivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zona_manejo_id')->nullable();
            ////$table->foreign('zona_manejo_id')->references('id')->on('zona_manejos');
            $table->unsignedBigInteger('tipo_cultivo_id')->nullable();
            ////$table->foreign('tipo_cultivo_id')->references('id')->on('tipo_cultivos');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zona_manejos_tipo_cultivos');
    }
};
