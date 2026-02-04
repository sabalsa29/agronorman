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
        Schema::create('correctivos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 150);
                $table->string('unidad_medida', 50);
                $table->string('efecto_esperado', 255)->nullable();
                $table->timestamps();

                // Opcional: evitar nombres duplicados
                $table->unique('nombre');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correctivos');
    }
};
