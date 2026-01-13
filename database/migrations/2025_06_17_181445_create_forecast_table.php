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
        Schema::create('forecast', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcelas');
            $table->date('fecha_solicita');
            $table->string('hora_solicita');
            $table->decimal('lat', 10, 7);
            $table->decimal('lon', 10, 7);
            $table->date('fecha_prediccion');
            $table->dateTime('sunriseTime');
            $table->dateTime('sunsetTime');
            $table->decimal('temperatureHigh', 10, 2);
            $table->decimal('temperatureLow', 10, 2);
            $table->decimal('precipProbability', 10, 2);
            $table->text('hourly');
            $table->string('summary')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();

            // Add indexes for better performance
            $table->index('fecha_prediccion');
            $table->index('fecha_solicita');
            $table->index(['parcela_id', 'fecha_prediccion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast');
    }
};
