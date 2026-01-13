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
        Schema::create('tipo_suelos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_suelo');
            $table->integer('bajo')->nullable();
            $table->integer('optimo_min')->nullable();
            $table->integer('optimo_max')->nullable();
            $table->integer('alto')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_suelos');
    }
};
