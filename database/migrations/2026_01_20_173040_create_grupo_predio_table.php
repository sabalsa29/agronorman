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
        Schema::create('grupo_parcela', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grupo_id');
            $table->unsignedBigInteger('parcela_id');

            // Evita duplicados
            $table->unique(['grupo_id', 'parcela_id'], 'uq_grupo_parcela');

            // Índices
            $table->index('grupo_id', 'idx_grupo_parcela_grupo');
            $table->index('parcela_id', 'idx_grupo_parcela_parcela');
            // FKs
            $table->foreign('grupo_id')
                ->references('id')->on('grupos')
                ->onDelete('cascade');

            $table->foreign('parcela_id')
                ->references('id')->on('parcelas')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupo_parcela');
    }
};
