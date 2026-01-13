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
        Schema::create('parcelas_error_precipitacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcelas')->onDelete('cascade');
            $table->string('error_tipo')->default('api_error'); // api_error, zona_manejo_missing, etc.
            $table->text('error_mensaje');
            $table->integer('intentos_fallidos')->default(1);
            $table->timestamp('ultimo_intento')->useCurrent();
            $table->boolean('activo')->default(true); // true = ignorar, false = volver a intentar
            $table->timestamps();

            // Índices
            $table->index(['parcela_id', 'activo']);
            $table->index('error_tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcelas_error_precipitacion');
    }
};
