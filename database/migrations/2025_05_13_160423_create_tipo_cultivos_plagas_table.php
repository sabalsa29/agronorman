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
        Schema::create('tipo_cultivos_plagas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tipo_cultivo_id');
            ////$table->foreign('tipo_cultivo_id')->references('id')->on('tipo_cultivos')->onDelete('cascade');
            $table->unsignedBigInteger('plaga_id');
            ////$table->foreign('plaga_id')->references('id')->on('plagas')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_cultivos_plagas');
    }
};
