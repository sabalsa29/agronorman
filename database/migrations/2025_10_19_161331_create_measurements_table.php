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
    Schema::create('measurements', function (Blueprint $t) {
        $t->id();
        $t->string('imei', 32)->index();
        $t->bigInteger('transaction_id')->nullable()->index();
        $t->timestamp('measured_at_utc')->nullable()->index();

        // NPK (convertidos a unidades finales)
        $t->decimal('temp_npk_c', 6, 2)->nullable();   // /10
        $t->decimal('hum_npk_pct', 6, 2)->nullable();  // /10
        $t->decimal('ph_npk', 6, 2)->nullable();       // /100
        $t->integer('cond_us_cm')->nullable();         // sin dividir
        $t->integer('nit_mg_kg')->nullable();
        $t->integer('pot_mg_kg')->nullable();
        $t->integer('phos_mg_kg')->nullable();

        // CO2 sensor
        $t->decimal('temp_sns_c', 6, 2)->nullable();   // /100
        $t->decimal('hum_sns_pct', 6, 2)->nullable();  // /100
        $t->integer('co2_ppm')->nullable();            // /100

        // Meta
        $t->integer('voltaje_mv')->nullable();
        $t->integer('contador_mnsj')->nullable();
        $t->tinyInteger('tec')->nullable();            // 8=4G, 0=2G
        $t->string('ARS', 8)->nullable();
        $t->integer('TON')->nullable();
        $t->string('CELLID', 32)->nullable();
        $t->bigInteger('CIT')->nullable();
        $t->integer('SWV')->nullable();
        $t->string('MNC', 8)->nullable();
        $t->string('MCC', 8)->nullable();
        $t->string('RAT', 16)->nullable();
        $t->string('LAC', 16)->nullable();
        $t->string('PROJECT', 64)->nullable();
        $t->integer('RSRP')->nullable();
        $t->integer('RSRQ')->nullable();

        $t->json('raw_payload')->nullable(); // por auditoría
        $t->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
