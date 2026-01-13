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
        Schema::create('unidades_calor_plaga', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zona_manejo_id')->nullable();
            //$table->foreign('zona_manejo_id')->references('id')->on('zona_manejos')->onDelete('cascade');
            $table->unsignedBigInteger('plaga_id')->nullable();
            //$table->foreign('plaga_id')->references('id')->on('plagas')->onDelete('cascade');
            $table->string('uc')->nullable();
            $table->date('fecha');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_calor_plaga');
    }
};
