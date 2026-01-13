<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mejorar precisión de columnas numéricas sin afectar datos existentes
     */
    public function up(): void
    {
        Schema::table('resumen_temperaturas', function (Blueprint $table) {
            // Cambiar columnas de float a decimal para mayor precisión
            // Usar DECIMAL(10,4) para permitir hasta 6 dígitos enteros y 4 decimales
            $table->decimal('max_nocturna', 10, 4)->change();
            $table->decimal('min_nocturna', 10, 4)->change();
            $table->decimal('amp_nocturna', 10, 4)->change();
            $table->decimal('max_diurna', 10, 4)->change();
            $table->decimal('min_diurna', 10, 4)->change();
            $table->decimal('amp_diurna', 10, 4)->change();
            $table->decimal('max', 10, 4)->change();
            $table->decimal('min', 10, 4)->change();
            $table->decimal('amp', 10, 4)->change();
            $table->decimal('uc', 10, 4)->change();
            $table->decimal('uf', 10, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     * Revertir a float si es necesario
     */
    public function down(): void
    {
        Schema::table('resumen_temperaturas', function (Blueprint $table) {
            // Revertir a float (menos preciso pero compatible)
            $table->float('max_nocturna')->change();
            $table->float('min_nocturna')->change();
            $table->float('amp_nocturna')->change();
            $table->float('max_diurna')->change();
            $table->float('min_diurna')->change();
            $table->float('amp_diurna')->change();
            $table->float('max')->change();
            $table->float('min')->change();
            $table->float('amp')->change();
            $table->float('uc')->change();
            $table->float('uf')->change();
        });
    }
};
