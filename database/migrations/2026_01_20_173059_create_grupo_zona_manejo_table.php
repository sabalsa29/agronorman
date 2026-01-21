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
        Schema::create('grupo_zona_manejo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grupo_id');
            $table->unsignedBigInteger('zona_manejo_id');

            // Evita duplicados
            $table->unique(['grupo_id', 'zona_manejo_id'], 'uq_grupo_zona_manejo');

            // Índices
            $table->index('grupo_id', 'idx_grupo_zona_manejo_grupo');
            $table->index('zona_manejo_id', 'idx_grupo_zona_manejo_zona');
            // FKs
            $table->foreign('grupo_id')
                ->references('id')->on('grupos')
                ->onDelete('cascade');

            $table->foreign('zona_manejo_id')
                ->references('id')->on('zona_manejos')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupo_zona_manejo');
    }
};
