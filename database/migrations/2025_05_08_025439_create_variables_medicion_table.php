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
        // Schema::create('variables_medicion', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('nombre')->nullable();
        //     $table->string('slug')->nullable();
        //     $table->string('unidad')->nullable();
        //     $table->timestamps();
        //     $table->softDeletes();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //Schema::dropIfExists('variables_medicion');
    }
};
