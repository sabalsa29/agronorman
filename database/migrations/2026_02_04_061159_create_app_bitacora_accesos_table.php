<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_bitacora_accesos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('zona_manejo_id');
            $table->timestamps();

            $table->unique(['user_id', 'zona_manejo_id'], 'ux_user_zona');

            //$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Ajusta el nombre de tabla real de zonas si difiere:
            //$table->foreign('zona_manejo_id')->references('id')->on('zona_manejos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_bitacora_accesos');
    }
};
