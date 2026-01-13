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
        Schema::create('configuracion_mqtt_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('username', 100);
            $table->string('accion', 50); // login, logout, enviar_configuracion, etc.
            $table->text('descripcion');
            $table->json('datos_adicionales')->nullable(); // JSON con información adicional
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            //$table->foreign('usuario_id')->references('id')->on('configuracion_mqtt_usuarios')->onDelete('set null');
            $table->index('usuario_id');
            $table->index('accion');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_mqtt_logs');
    }
};
