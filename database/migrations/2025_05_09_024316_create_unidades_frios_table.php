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
        Schema::create('unidades_frio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zona_manejo_id')->nullable();
            //$table->foreign('zona_manejo_id')->references('id')->on('zona_manejos');
            $table->datetime('fecha');
            $table->float('unidades')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_frio');
    }
};
