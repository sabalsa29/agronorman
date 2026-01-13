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
        Schema::create('cliente_grupo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('grupo_id');
            $table->timestamps();

            // Foreign keys
            //$table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            //$table->foreign('grupo_id')->references('id')->on('grupos')->onDelete('cascade');

            // Unique constraint: un cliente no puede tener el mismo grupo asignado dos veces
            $table->unique(['cliente_id', 'grupo_id']);

            // Indexes
            $table->index('cliente_id');
            $table->index('grupo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_grupo');
    }
};
