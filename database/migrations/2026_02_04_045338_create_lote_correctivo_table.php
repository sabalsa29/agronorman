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
       Schema::create('lote_correctivo', function (Blueprint $table) {
            $table->id();

            // FK a lotes (ajusta el nombre de tabla si la tuya no es "lotes")
            // $table->foreignId('lote_id')
            //     ->constrained('lotes')
            //     ->cascadeOnDelete();

            $table->unsignedBigInteger('lote_id');

            $table->unsignedBigInteger('correctivo_id');

            $table->date('fecha_aplicacion');

            // "cantidad sugerida" (ajusta precision/scale si lo necesitas)
            $table->decimal('cantidad_sugerida', 12, 3)->default(0);

            $table->timestamps();

            // Índices útiles para consultas por lote/año
            $table->index(['lote_id', 'fecha_aplicacion']);
            $table->index(['correctivo_id', 'fecha_aplicacion']);

            // Si quieres evitar duplicados exactos (mismo lote + correctivo + misma fecha), descomenta:
            // $table->unique(['lote_id', 'correctivo_id', 'fecha_aplicacion'], 'uniq_lote_correctivo_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lote_correctivo');
    }
};
