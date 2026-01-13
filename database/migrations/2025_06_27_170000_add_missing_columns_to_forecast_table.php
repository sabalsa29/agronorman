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
        Schema::table('forecast', function (Blueprint $table) {
            $table->decimal('uvindex', 5, 2)->nullable()->after('icon');
            $table->dateTime('temperatureHighTime')->nullable()->after('temperatureHigh');
            $table->dateTime('temperatureLowTime')->nullable()->after('temperatureLow');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forecast', function (Blueprint $table) {
            $table->dropColumn(['uvindex', 'temperatureHighTime', 'temperatureLowTime']);
        });
    }
};
