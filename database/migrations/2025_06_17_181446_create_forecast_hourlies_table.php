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
        Schema::create('forecast_hourlies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forecast_id')->constrained('forecast');
            $table->foreignId('parcela_id')->constrained('parcelas');
            $table->date('fecha');
            $table->decimal('humedad_relativa', 10, 2);
            $table->decimal('temperatura', 10, 2);
            $table->timestamps();

            // Add indexes for better performance
            $table->index('fecha');
            $table->index(['forecast_id', 'parcela_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_hourlies');
    }
};
