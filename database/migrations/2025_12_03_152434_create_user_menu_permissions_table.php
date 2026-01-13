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
        Schema::create('user_menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            //$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('menu_key'); // Ej: "usuarios", "usuarios.clientes", "estaciones.fabricantes"
            $table->enum('menu_type', ['main', 'sub']); // main = liga principal, sub = liga secundaria
            $table->string('parent_key')->nullable(); // Si es sub, referencia a la principal (ej: "usuarios")
            $table->boolean('permitted')->default(true); // true = permitido, false = bloqueado
            $table->timestamps();
            
            // Índice único para evitar duplicados
            $table->unique(['user_id', 'menu_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_menu_permissions');
    }
};
