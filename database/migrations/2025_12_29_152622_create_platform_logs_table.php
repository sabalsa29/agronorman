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
        Schema::create('platform_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('username', 100);
            $table->string('seccion', 50); // clientes, grupos, zonas_manejo, etc.
            $table->string('accion', 50); // crear, editar, eliminar, ver, etc.
            $table->string('entidad_tipo', 100); // Clientes, Grupos, ZonaManejos, etc.
            $table->unsignedBigInteger('entidad_id')->nullable(); // ID del registro afectado
            $table->text('descripcion');
            $table->json('datos_anteriores')->nullable(); // Datos antes de la modificación
            $table->json('datos_nuevos')->nullable(); // Datos después de la modificación
            $table->json('datos_adicionales')->nullable(); // Información adicional
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            //$table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            $table->index('usuario_id');
            $table->index('seccion');
            $table->index('accion');
            $table->index('entidad_tipo');
            $table->index('entidad_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_logs');
    }
};
