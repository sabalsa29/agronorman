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
        Schema::create('user_grupo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('grupo_id');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'grupo_id'], 'uq_user_grupo');

            // Índices
            // $table->index('user_id', 'idx_user_grupo_user');
            // $table->index('grupo_id', 'idx_user_grupo_grupo');

            // $table->foreign('user_id')
            //     ->references('id')->on('users')
            //     ->onDelete('cascade');

            // $table->foreign('grupo_id')
            //     ->references('id')->on('grupos')
            //     ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_grupo');
    }
};
