<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResumenTemperaturasTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resumen_temperaturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zona_manejo_id')->nullable();
            //$table->foreign('zona_manejo_id')->references('id')->on('zona_manejos');
            $table->date('fecha');
            $table->float('max_nocturna');
            $table->float('min_nocturna');
            $table->float('amp_nocturna');
            $table->float('max_diurna');
            $table->float('min_diurna');
            $table->float('amp_diurna');
            $table->float('max');
            $table->float('min');
            $table->float('amp');
            $table->float('uc');
            $table->float('uf')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Si necesitas forzar collation/charset específicos:
        // DB::statement("ALTER TABLE resumen_temperaturas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resumen_temperaturas');
    }
}
