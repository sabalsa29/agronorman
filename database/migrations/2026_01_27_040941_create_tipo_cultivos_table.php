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
        Schema::create('tipo_cultivos', function (Blueprint $table) {
            $table->id();

            // FK hacia cultivos.id (ajusta el nombre de la tabla si la tuya no es "cultivos")
            $table->foreignId('cultivo_id')
                ->constrained('cultivos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('nombre', 255);

            // 1 = activo, 0 = inactivo (puedes cambiar defaults si en tu sistema manejas otros valores)
            $table->tinyInteger('status')->default(1);

            $table->timestamps();

            $table->index(['cultivo_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_cultivos');
    }
};
