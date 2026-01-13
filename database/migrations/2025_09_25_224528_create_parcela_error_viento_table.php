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
        Schema::create('parcela_error_viento', function (Blueprint $table) {
            $table->id();
            //$table->foreignId('parcela_id')->constrained('parcelas');
            $table->string('error_tipo')->default('api_error');
            $table->text('error_mensaje');
            $table->integer('intentos_fallidos')->default(1);
            $table->timestamp('ultimo_intento');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcela_error_viento');
    }
};
