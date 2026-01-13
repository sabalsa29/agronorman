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
        Schema::create('estacion_variable', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estacion_id')->nullable();
            //$table->foreign('estacion_id')->references('id')->on('estaciones')->onDelete('cascade');
            $table->unsignedBigInteger('variables_medicion_id')->nullable();
            //$table->foreign('variables_medicion_id')->references('id')->on('variables_medicion')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estacion_variable');
    }
};
