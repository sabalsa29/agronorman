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
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('grupo_id')->nullable();
            $table->unsignedBigInteger('parcela_id')->nullable();
            $table->unsignedBigInteger('zona_manejo_id');

            // Evita duplicados
            //$table->unique(['grupo_id', 'zona_manejo_id'], 'uq_grupo_zona_manejo');

            // Índices
            $table->index('user_id', 'idx_grupo_zona_manejo_user');
            $table->index('grupo_id', 'idx_grupo_zona_manejo_grupo');
            $table->index('zona_manejo_id', 'idx_grupo_zona_manejo_zona');
            $table->index('parcela_id', 'idx_grupo_zona_manejo_parcela');
            // FKs

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
                
            $table->foreign('grupo_id')
                ->references('id')->on('grupos')
                ->nullOnDelete(); 

            $table->foreign('parcela_id')
                ->references('id')->on('parcelas')
                ->nullOnDelete(); 

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
